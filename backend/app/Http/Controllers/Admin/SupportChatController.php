<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportChatNote;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class SupportChatController extends Controller
{
    /**
     * Список всех чатов
     */
    public function index(Request $request)
    {
        // Оптимизация: загружаем только нужные поля и связи
        // Не используем select() для lastMessage, так как latestOfMany() создает сложный JOIN запрос
        $query = SupportChat::with([
                'user:id,name,email',
                'assignedAdmin:id,name,email',
                'lastMessage' // Загружаем без select для избежания конфликтов с latestOfMany()
            ])
            ->withCount([
                'messages as unread_count' => function($query) {
                    $query->where('is_read', false)
                        ->whereIn('sender_type', [
                            SupportMessage::SENDER_USER, 
                            SupportMessage::SENDER_GUEST
                        ]);
                }
            ])
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc');
        
        // Фильтр по источнику (вкладки: все/сайт/telegram)
        if ($request->has('source') && $request->source !== '') {
            if ($request->source === 'telegram') {
                $query->fromTelegram();
            } elseif ($request->source === 'website') {
                $query->fromWebsite();
            }
        }
        
        // Фильтры
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('assigned_to') && $request->assigned_to !== '') {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        $chats = $query->paginate(20);
        
        return view('admin.support-chats.index', compact('chats'));
    }
    
    /**
     * Просмотр чата
     */
    public function show($id)
    {
        // Оптимизация: загружаем чат без всех сообщений
        $chat = SupportChat::with(['user:id,name,email', 'assignedAdmin:id,name,email'])
            ->findOrFail($id);
        
        // ВАЖНО: Защита от ID Enumeration. 
        // Если чат назначен на другого менеджера, обычный админ не может его просматривать.
        $admin = auth()->user();
        if ($chat->assigned_to && $chat->assigned_to !== $admin->id && !$admin->is_main_admin) {
            return redirect()->route('admin.support-chats.index')->with('error', 'Этот чат назначен на другого администратора.');
        }

        // Отмечаем сообщения от пользователей/гостей как прочитанные
        // Делаем это в транзакции для атомарности
        DB::transaction(function() use ($chat) {
        $chat->messages()
            ->fromUserOrGuest()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        // Если чат в статусе "pending", автоматически переводим в "open"
        if ($chat->status === SupportChat::STATUS_PENDING) {
            $chat->update([
                'status' => SupportChat::STATUS_OPEN,
            ]);
        }
        });
        
        // Очищаем кеш счетчика непрочитанных сообщений для этого чата
        $chat->clearUnreadCountCache();
        
        // Загружаем заметки администраторов с оптимизацией
        $chat->load(['notes' => function($q) {
            $q->with('user:id,name')->latest();
        }]);
        
        // Load messages in chronological order (oldest first)
        $limit = (int) request()->input('limit', 50);
        $limit = max(10, min(200, $limit));
        
        $chat->load(['messages' => function($q) use ($limit) {
            $q->with(['user:id,name,email', 'attachments'])
              ->orderBy('created_at', 'asc')
              ->orderBy('id', 'asc')
              ->limit($limit);
        }]);
        
        return view('admin.support-chats.show', compact('chat'));
    }
    
    /**
     * Отправить сообщение от администратора
     */
    public function sendMessage(Request $request, $id, TelegramBotService $telegramService)
    {
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();

        // ВАЖНО: Проверка прав. Если чат назначен на другого админа, 
        // обычный админ не может в него писать (только Main Admin).
        if ($chat->assigned_to && $chat->assigned_to !== $admin->id && !$admin->is_main_admin) {
            return back()->withErrors(['message' => 'Этот чат назначен на другого администратора.']);
        }

        $request->validate([
            'message' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:5', // Максимум 5 файлов
            'attachments.*' => 'file|mimes:jpeg,png,jpg,webp,pdf|max:10240', // 10MB max per file, разрешены только изображения и PDF
        ]);

        // ВАЖНО: Проверка общего объема вложений в чате (Storage DOS protection)
        $currentTotalSize = $chat->getTotalAttachmentsSize();
        $chatMaxTotalSize = 200 * 1024 * 1024; // 200MB лимит на чат для админов (больше чем для пользователей)
        
        $incomingSize = 0;
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $incomingSize += $file->getSize();
            }
        }

        if (($currentTotalSize + $incomingSize) > $chatMaxTotalSize) {
             return back()->withErrors(['message' => 'Превышен лимит вложений для этого чата (200MB). Удалите старые файлы или отправьте меньше вложений.']);
        }

        // Проверка свободного места на диске
        $freeSpace = disk_free_space(storage_path('app'));
        if ($freeSpace !== false && $freeSpace < (500 * 1024 * 1024)) { // 500MB для админов
            return back()->withErrors(['message' => 'Недостаточно места на сервере для сохранения файлов.']);
        }

        $admin = $request->user();
        
        $messageText = trim($request->input('message', ''));
        
        // Создаем сообщение и файлы в транзакции для атомарности
        $message = DB::transaction(function() use ($chat, $admin, $messageText, $request) {
        $message = SupportMessage::create([
            'support_chat_id' => $chat->id,
            'user_id' => $admin->id,
            'sender_type' => SupportMessage::SENDER_ADMIN,
                'message' => $messageText,
            'is_read' => false,
        ]);
        
            // Обработка вложений в той же транзакции
        if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Получаем информацию о файле
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $fileSize = $file->getSize();
                    
                    // Сохраняем файл используя Storage (как в других контроллерах)
                    $directory = 'support-chat/attachments/' . date('Y/m');
                    $path = $file->store($directory, 'public');
                    
                    // Генерируем URL используя Storage::url()
                    $fileUrl = Storage::url($path);
                
                SupportMessageAttachment::create([
                    'support_message_id' => $message->id,
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'file_url' => $fileUrl,
                        'mime_type' => $mimeType,
                        'file_size' => $fileSize,
                    ]);
                }
            }
            
            return $message;
        });

        // Загружаем вложения, чтобы передать их в Telegram
        // Обновляем модель после транзакции, чтобы получить связи
        $message->refresh();
        $message->load('attachments');
        
        // Send message to Telegram if chat is from Telegram
        if ($chat->isFromTelegram() && $chat->telegram_chat_id) {
            $textToSend = trim($message->message ?? $messageText ?? '');
            $telegramChatId = (int) $chat->telegram_chat_id;
            
            try {
                if (empty($textToSend) && $message->attachments->isEmpty()) {
                    Log::warning('No content to send to Telegram', [
                        'chat_id' => $chat->id,
                        'message_id' => $message->id,
                    ]);
                } elseif ($telegramChatId <= 0) {
                    Log::error('Invalid telegram_chat_id', [
                        'chat_id' => $chat->id,
                        'telegram_chat_id' => $telegramChatId,
                    ]);
                    \Illuminate\Support\Facades\Session::flash('telegram_send_error', 'Invalid Telegram chat ID. Message saved to database.');
                } elseif (!\App\Models\Option::get('telegram_client_enabled', false)) {
                    Log::warning('Telegram Client not enabled', ['chat_id' => $chat->id]);
                    \Illuminate\Support\Facades\Session::flash('telegram_send_error', 'Telegram Client is not enabled. Enable it in Settings → Telegram.');
                } else {
                    $success = $telegramService->sendMessage($telegramChatId, $textToSend, $message->attachments);
                    
                    if ($success) {
                        Log::info('Message sent to Telegram', [
                            'chat_id' => $chat->id,
                            'telegram_chat_id' => $telegramChatId,
                            'message_id' => $message->id,
                        ]);
                    } else {
                        Log::error('Failed to send message to Telegram', [
                            'chat_id' => $chat->id,
                            'telegram_chat_id' => $telegramChatId,
                            'message_id' => $message->id,
                        ]);
                        \Illuminate\Support\Facades\Session::flash('telegram_send_error', 'Message saved but failed to send to Telegram. Check logs and Telegram settings.');
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error sending message to Telegram: ' . $e->getMessage(), [
                    'chat_id' => $chat->id,
                    'telegram_chat_id' => $telegramChatId,
                    'message_id' => $message->id,
                    'error_type' => get_class($e),
                ]);
            }
        }
        
        // Останавливаем индикатор печати
        $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
        Cache::forget($key);
        
        // Обновляем время последнего сообщения
        $chat->update([
            'last_message_at' => now(),
            'status' => SupportChat::STATUS_OPEN,
        ]);
        
        // Очищаем кеш счетчика непрочитанных сообщений
        $chat->clearUnreadCountCache();
        
        return redirect()->back()->with('success', 'Сообщение отправлено');
    }
    
    /**
     * Send typing indicator from admin
     */
    public function sendTyping(Request $request, $id)
    {
        try {
            $chat = SupportChat::findOrFail($id);
            $admin = $request->user();
            
            $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
            Cache::put($key, true, 5);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Stop typing indicator from admin
     */
    public function stopTyping(Request $request, $id)
    {
        try {
            $chat = SupportChat::findOrFail($id);
            $admin = $request->user();
            
            $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
            Cache::forget($key);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get user typing status
     */
    public function getUserTypingStatus($id)
    {
        try {
            $chat = SupportChat::findOrFail($id);
            $isTyping = false;
            
            if ($chat->user_id) {
                $key = 'support_chat_typing_' . $chat->id . '_' . $chat->user_id;
                $isTyping = Cache::has($key);
            } elseif ($chat->guest_email) {
                $emailKey = md5($chat->guest_email);
                $key = 'support_chat_typing_' . $chat->id . '_' . $emailKey;
                $isTyping = Cache::has($key);
            }
            
            return response()->json([
                'success' => true,
                'is_typing' => $isTyping,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'is_typing' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Назначить администратора на чат
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
        ]);
        
        $chat = SupportChat::findOrFail($id);
        $chat->update([
            'assigned_to' => $request->admin_id,
        ]);
        
        return redirect()->back()->with('success', 'Администратор назначен');
    }
    
    /**
     * Изменить статус чата
     */
    public function updateStatus(Request $request, $id)
    {
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();

        // Проверка прав на изменение статуса
        if ($chat->assigned_to && $chat->assigned_to !== $admin->id && !$admin->is_main_admin) {
            return redirect()->back()->with('error', 'У вас нет прав на изменение статуса этого чата.');
        }

        $request->validate([
            'status' => 'required|in:open,closed,pending',
        ]);
        $oldStatus = $chat->status;

        // Используем транзакцию для атомарности обновления статуса и создания сообщения
        DB::transaction(function() use ($chat, $request, $oldStatus) {
        $chat->update([
            'status' => $request->status,
        ]);

        // Если чат переведен в статус "closed", добавляем системное сообщение
        if ($oldStatus !== SupportChat::STATUS_CLOSED && $chat->status === SupportChat::STATUS_CLOSED) {
            SupportMessage::create([
                'support_chat_id' => $chat->id,
                'user_id' => $request->user()->id ?? null,
                'sender_type' => SupportMessage::SENDER_ADMIN,
                'message' => 'Диалог закрыт администратором. Если у вас появятся новые вопросы — создайте новый диалог.',
                'is_read' => false,
            ]);

            $chat->update([
                'last_message_at' => now(),
            ]);
        }
        });
        
        // Очищаем кеш счетчика непрочитанных сообщений
        $chat->clearUnreadCountCache();
        
        return redirect()->back()->with('success', 'Статус обновлен');
    }
    
    /**
     * Добавить заметку к чату
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|max:2000',
        ]);
        
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();
        
        SupportChatNote::create([
            'support_chat_id' => $chat->id,
            'user_id' => $admin->id,
            'note' => trim($request->note),
        ]);
        
        return redirect()->back()->with('success', 'Заметка добавлена');
    }
    
    /**
     * Удалить заметку
     */
    public function deleteNote(Request $request, $id, $noteId)
    {
        $chat = SupportChat::findOrFail($id);
        $note = SupportChatNote::findOrFail($noteId);
        $admin = $request->user();
        
        // Проверяем, что заметка принадлежит этому чату
        if ($note->support_chat_id !== $chat->id) {
            abort(404);
        }
        
        // Только создатель заметки или главный администратор может удалить
        if ($note->user_id !== $admin->id && !$admin->is_main_admin) {
            abort(403, 'У вас нет прав на удаление этой заметки');
        }
        
        $note->delete();
        
        return redirect()->back()->with('success', 'Заметка удалена');
    }
    
    /**
     * Get unread messages count for admin
     */
    public function getUnreadCount()
    {
        try {
            $unreadCount = SupportMessage::whereHas('chat', function($query) {
                $query->where('status', '!=', SupportChat::STATUS_CLOSED);
            })
            ->fromUserOrGuest()
            ->where('is_read', false)
            ->count();
            
            return response()->json(['count' => $unreadCount]);
        } catch (\Exception $e) {
            return response()->json(['count' => 0, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get new messages for admin (polling)
     */
    public function getMessages(Request $request, $id)
    {
        try {
            $chat = SupportChat::findOrFail($id);
            $admin = auth()->user();

            // ВАЖНО: Защита доступа к сообщениям через API
            if ($chat->assigned_to && $chat->assigned_to !== $admin->id && !$admin->is_main_admin) {
                return response()->json(['success' => false, 'error' => 'Доступ запрещен'], 403);
            }

            $lastMessageId = (int) $request->input('last_message_id', 0);
            
            $messages = $chat->messages()
                ->with(['user:id,name,email', 'attachments'])
                ->where('id', '>', $lastMessageId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            // Mark new user/guest messages as read
            $newUserMessages = $messages->filter(function($message) {
                return in_array($message->sender_type, [SupportMessage::SENDER_USER, SupportMessage::SENDER_GUEST]);
            });
            
            if ($newUserMessages->isNotEmpty()) {
                SupportMessage::whereIn('id', $newUserMessages->pluck('id'))
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);
                
                $chat->clearUnreadCountCache();
            }
            
            return response()->json([
                'success' => true,
                'messages' => $messages->map(function($message) {
                    return [
                        'id' => $message->id,
                        'message' => $message->message,
                        'sender_type' => $message->sender_type,
                        'user' => $message->user ? [
                            'id' => $message->user->id,
                            'name' => $message->user->name,
                            'email' => $message->user->email,
                        ] : null,
                        'created_at' => $message->created_at->toISOString(),
                        'attachments' => $message->attachments->map(function($attachment) {
                            return [
                                'id' => $attachment->id,
                                'file_name' => $attachment->file_name,
                                'file_url' => $attachment->file_url ?? $attachment->full_url,
                                'file_size' => $attachment->file_size,
                                'mime_type' => $attachment->mime_type,
                            ];
                        }),
                    ];
                }),
                'chat' => [
                    'id' => $chat->id,
                    'status' => $chat->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting messages for admin', [
                'chat_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error getting messages',
                'messages' => [],
            ], 500);
        }
    }
}
