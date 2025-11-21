<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportChatNote;
use App\Services\TelegramClientService;
use Illuminate\Http\Request;
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
        
        // Отмечаем сообщения от пользователей/гостей как прочитанные
        // Делаем это в транзакции для атомарности
        \Illuminate\Support\Facades\DB::transaction(function() use ($chat) {
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
        
        // Оптимизация: загружаем только последние сообщения для первоначальной загрузки
        // Вместо всех сообщений загружаем последние 200 (для больших чатов)
        $chat->load(['messages' => function($q) {
            $q->with(['user:id,name,email', 'attachments'])
              ->orderBy('created_at', 'desc')
              ->limit(200); // Последние 200 сообщений для начальной загрузки
        }]);
        
        // Переворачиваем коллекцию, чтобы старые сообщения были сначала (для отображения в чате)
        if ($chat->messages) {
            $chat->messages = $chat->messages->reverse()->values();
        }
        
        return view('admin.support-chats.show', compact('chat'));
    }
    
    /**
     * Отправить сообщение от администратора
     */
    public function sendMessage(Request $request, $id, TelegramClientService $telegramService)
    {
        $request->validate([
            'message' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:5', // Максимум 5 файлов
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip,rar|max:10240', // 10MB max per file
        ]);
        
        // Проверяем, что есть либо сообщение, либо вложения
        if (!$request->filled('message') && !$request->hasFile('attachments')) {
            return back()->withErrors(['message' => 'Введите сообщение или прикрепите файлы']);
        }
        
        // Дополнительная валидация: проверка общего размера всех файлов (максимум 50MB)
        if ($request->hasFile('attachments')) {
            $totalSize = 0;
            $maxTotalSize = 50 * 1024 * 1024; // 50MB общий лимит
            
            foreach ($request->file('attachments') as $file) {
                $totalSize += $file->getSize();
            }
            
            if ($totalSize > $maxTotalSize) {
                return back()->withErrors(['attachments' => 'Общий размер всех файлов не должен превышать 50MB']);
            }
            
            // Проверка доступного места на диске (минимум 100MB должно остаться)
            $freeSpace = disk_free_space(public_path());
            if ($freeSpace !== false && $freeSpace < (100 * 1024 * 1024)) {
                return back()->withErrors(['attachments' => 'Недостаточно места на диске для сохранения файлов']);
            }
        }
        
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();
        
        $messageText = trim($request->input('message', ''));
        
        // Создаем сообщение и файлы в транзакции для атомарности
        $message = \Illuminate\Support\Facades\DB::transaction(function() use ($chat, $admin, $messageText, $request) {
            $message = SupportMessage::create([
                'support_chat_id' => $chat->id,
                'user_id' => $admin->id,
                'sender_type' => SupportMessage::SENDER_ADMIN,
                'message' => $messageText,
                'is_read' => false,
            ]);
            
            // Обработка вложений в той же транзакции
            if ($request->hasFile('attachments')) {
                // Сохраняем напрямую в public (без символических ссылок для совместимости с Windows)
                $directory = 'support-chat/attachments/' . date('Y/m');
                $fullDirectory = public_path($directory);
                
                if (!file_exists($fullDirectory)) {
                    mkdir($fullDirectory, 0755, true);
                }
                
                foreach ($request->file('attachments') as $file) {
                    // Получаем информацию о файле ДО перемещения
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $mimeType = $file->getMimeType();
                    $fileSize = $file->getSize();
                    
                    // Генерируем уникальное имя файла
                    $fileName = pathinfo($originalName, PATHINFO_FILENAME);
                    $fileName = \Illuminate\Support\Str::slug($fileName) . '_' . time() . '_' . uniqid() . '.' . $extension;
                    
                    // Перемещаем файл
                    $file->move($fullDirectory, $fileName);
                    
                    // Формируем относительный путь
                    $relativePath = $directory . '/' . $fileName;
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $fileUrl = asset($relativePath);
                    
                    SupportMessageAttachment::create([
                        'support_message_id' => $message->id,
                        'file_name' => $originalName,
                        'file_path' => $relativePath,
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
        
        // Если чат из Telegram, отправляем сообщение в Telegram
        // Перехватываем вывод, чтобы MadelineProto не вывел HTML
        if ($chat->isFromTelegram() && $chat->telegram_chat_id) {
            // Используем текст сообщения из базы данных (на случай если он был изменен)
            // Если сообщение пустое, используем пустую строку (для вложений без текста)
            $textToSend = trim($message->message ?? $messageText ?? '');
            
            // Приводим telegram_chat_id к числу (может быть строкой из БД)
            $telegramChatId = (int) $chat->telegram_chat_id;
            
            \Illuminate\Support\Facades\Log::info('Попытка отправить сообщение в Telegram', [
                'chat_id' => $chat->id,
                'telegram_chat_id' => $telegramChatId,
                'telegram_chat_id_original' => $chat->telegram_chat_id,
                'telegram_chat_id_type' => gettype($chat->telegram_chat_id),
                'message_id' => $message->id,
                'has_text' => !empty($textToSend),
                'text_length' => strlen($textToSend),
                'text_preview' => substr($textToSend, 0, 100),
                'has_attachments' => $message->attachments->isNotEmpty(),
                'attachments_count' => $message->attachments->count(),
            ]);
            
            ob_start();
            try {
                // Проверяем, что есть что отправлять
                if (empty($textToSend) && $message->attachments->isEmpty()) {
                    ob_end_clean();
                    \Illuminate\Support\Facades\Log::warning('Нет содержимого для отправки в Telegram', [
                        'chat_id' => $chat->id,
                        'telegram_chat_id' => $telegramChatId,
                        'message_id' => $message->id,
                    ]);
                } else {
                    // Проверяем, что telegram_chat_id валидный
                    if ($telegramChatId <= 0) {
                        ob_end_clean();
                        \Illuminate\Support\Facades\Log::error('Некорректный telegram_chat_id для отправки сообщения', [
                            'chat_id' => $chat->id,
                            'telegram_chat_id' => $telegramChatId,
                            'telegram_chat_id_original' => $chat->telegram_chat_id,
                        ]);
                        \Illuminate\Support\Facades\Session::flash('telegram_send_error', 'Некорректный ID Telegram чата. Сообщение сохранено в базе данных.');
                    } else {
                        // Проверяем, включен ли Telegram Client
                        $telegramEnabled = \App\Models\Option::get('telegram_client_enabled', false);
                        if (!$telegramEnabled) {
                            ob_end_clean();
                            \Illuminate\Support\Facades\Log::warning('Telegram Client не включен в настройках', [
                                'chat_id' => $chat->id,
                            ]);
                            \Illuminate\Support\Facades\Session::flash('telegram_send_error', 'Telegram Client не включен в настройках. Включите его в Настройках → Telegram.');
                        } else {
                            // Отправляем сообщение в Telegram
                            $success = $telegramService->sendMessage($telegramChatId, $textToSend, $message->attachments);
                            
                            // Получаем вывод после отправки
                            $output = '';
                            if (ob_get_level() > 0) {
                                $output = ob_get_clean();
                            }
                            
                            // Если был вывод HTML, логируем это
                            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                                \Illuminate\Support\Facades\Log::warning('MadelineProto вывел HTML при отправке сообщения из SupportChatController', [
                                    'output' => substr($output, 0, 500)
                                ]);
                            }
                            
                            if ($success) {
                                \Illuminate\Support\Facades\Log::info('Сообщение успешно отправлено в Telegram', [
                                    'chat_id' => $chat->id,
                                    'telegram_chat_id' => $telegramChatId,
                                    'message_id' => $message->id,
                                ]);
                            } else {
                                \Illuminate\Support\Facades\Log::error('Не удалось отправить сообщение в Telegram (sendMessage вернул false)', [
                                    'chat_id' => $chat->id,
                                    'telegram_chat_id' => $telegramChatId,
                                    'message_id' => $message->id,
                                    'has_text' => !empty($textToSend),
                                    'has_attachments' => $message->attachments->isNotEmpty(),
                                ]);
                                
                                // Сохраняем флаг ошибки для отображения пользователю
                                \Illuminate\Support\Facades\Session::flash('telegram_send_error', 'Сообщение сохранено, но не удалось отправить в Telegram. Проверьте логи и настройки Telegram. Убедитесь, что Telegram Client авторизован.');
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                ob_end_clean();
                \Illuminate\Support\Facades\Log::error('Ошибка отправки сообщения в Telegram: ' . $e->getMessage(), [
                    'chat_id' => $chat->id,
                    'telegram_chat_id' => $telegramChatId,
                    'telegram_chat_id_original' => $chat->telegram_chat_id,
                    'message_id' => $message->id,
                    'error_type' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
                // Продолжаем выполнение, даже если отправка в Telegram не удалась
            }
        } else {
            // Логируем, почему сообщение не отправляется в Telegram
            \Illuminate\Support\Facades\Log::warning('Сообщение не отправляется в Telegram - проверка не пройдена', [
                'chat_id' => $chat->id,
                'source' => $chat->source,
                'isFromTelegram' => $chat->isFromTelegram(),
                'telegram_chat_id' => $chat->telegram_chat_id,
                'telegram_chat_id_type' => gettype($chat->telegram_chat_id),
                'telegram_chat_id_empty' => empty($chat->telegram_chat_id),
            ]);
        }
        
        // Останавливаем индикатор печати
        $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
        \Illuminate\Support\Facades\Cache::forget($key);
        
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
     * Отправить событие "печатает" от администратора
     */
    public function sendTyping(Request $request, $id)
    {
        // Перехватываем вывод, чтобы MadelineProto не испортил JSON-ответ
        ob_start();
        
        try {
            $chat = SupportChat::findOrFail($id);
            $admin = $request->user();
            
            $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
            \Illuminate\Support\Facades\Cache::put($key, true, 5); // 5 секунд
            
            // Очищаем весь перехваченный вывод перед отправкой JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Очищаем вывод даже при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
    }
    
    /**
     * Остановить событие "печатает" от администратора
     */
    public function stopTyping(Request $request, $id)
    {
        // Перехватываем вывод, чтобы MadelineProto не испортил JSON-ответ
        ob_start();
        
        try {
            $chat = SupportChat::findOrFail($id);
            $admin = $request->user();
            
            $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
            \Illuminate\Support\Facades\Cache::forget($key);
            
            // Очищаем весь перехваченный вывод перед отправкой JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Очищаем вывод даже при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
    }
    
    /**
     * Получить статус печати пользователя
     */
    public function getUserTypingStatus($id)
    {
        // Перехватываем вывод, чтобы MadelineProto не испортил JSON-ответ
        ob_start();
        
        try {
            $chat = SupportChat::findOrFail($id);
            
            // Проверяем, печатает ли пользователь/гость
            $isTyping = false;
            
            if ($chat->user_id) {
                // Для авторизованного пользователя
                $key = 'support_chat_typing_' . $chat->id . '_' . $chat->user_id;
                $isTyping = \Illuminate\Support\Facades\Cache::has($key);
            } else if ($chat->guest_email) {
                // Для гостя - проверяем все возможные ключи (так как email может быть в разных форматах)
                $emailKey = md5($chat->guest_email);
                $key = 'support_chat_typing_' . $chat->id . '_' . $emailKey;
                $isTyping = \Illuminate\Support\Facades\Cache::has($key);
            }
            
            // Очищаем весь перехваченный вывод перед отправкой JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return response()->json([
                'success' => true,
                'is_typing' => $isTyping,
            ]);
        } catch (\Exception $e) {
            // Очищаем вывод даже при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
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
        $request->validate([
            'status' => 'required|in:open,closed,pending',
        ]);
        
        $chat = SupportChat::findOrFail($id);
        $oldStatus = $chat->status;

        // Используем транзакцию для атомарности обновления статуса и создания сообщения
        \Illuminate\Support\Facades\DB::transaction(function() use ($chat, $request, $oldStatus) {
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
     * Получить количество непрочитанных сообщений для админа
     */
    public function getUnreadCount()
    {
        // Перехватываем вывод, чтобы MadelineProto не испортил JSON-ответ
        ob_start();
        
        try {
            $unreadCount = SupportMessage::whereHas('chat', function($query) {
                $query->where('status', '!=', SupportChat::STATUS_CLOSED);
            })
            ->fromUserOrGuest()
            ->where('is_read', false)
            ->count();
            
            // Очищаем весь перехваченный вывод перед отправкой JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return response()->json(['count' => $unreadCount]);
        } catch (\Exception $e) {
            // Очищаем вывод даже при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
    }
    
    /**
     * Получить новые сообщения для админа (для polling)
     */
    public function getMessages(Request $request, $id)
    {
        // Перехватываем вывод, чтобы MadelineProto не испортил JSON-ответ
        ob_start();
        
        try {
            $chat = SupportChat::findOrFail($id);
            $lastMessageId = (int) $request->input('last_message_id', 0);
            
            // Получаем сообщения, которые появились после last_message_id
            $messages = $chat->messages()
                ->with(['user:id,name,email', 'attachments'])
                ->where('id', '>', $lastMessageId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc') // Дополнительная сортировка для надежности
                ->get();
            
            // Отмечаем новые сообщения от пользователей/гостей как прочитанные
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
                
                // Очищаем кеш счетчика непрочитанных сообщений
                $chat->clearUnreadCountCache();
            }
            
            // Очищаем весь перехваченный вывод перед отправкой JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
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
            // Очищаем вывод даже при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Логируем ошибку, но не пробрасываем её, чтобы не сломать polling
            \Illuminate\Support\Facades\Log::error('Ошибка получения сообщений для админа', [
                'chat_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Ошибка получения сообщений',
                'messages' => [],
            ], 500);
        }
    }
}
