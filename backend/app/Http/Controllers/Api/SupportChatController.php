<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportMessageReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SupportChatController extends Controller
{
    /**
     * Получить или создать чат для текущего пользователя
     */
    public function getOrCreateChat(Request $request)
    {
        $user = $request->user('sanctum');
        
        // Для авторизованных пользователей
        if ($user) {
            // Проверяем, есть ли незакрытый чат
            $existingChat = SupportChat::where('user_id', $user->id)
                ->notClosed()
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($existingChat) {
                $existingChat->load(['messages' => function($query) {
                    $query->orderBy('created_at', 'asc')->with(['user', 'attachments']);
                }, 'user', 'assignedAdmin']);
                
                return response()->json([
                    'success' => true,
                    'chat' => $existingChat,
                ]);
            }
            
            // Создаём новый чат
            $chat = SupportChat::create([
                'user_id' => $user->id,
                'status' => SupportChat::STATUS_PENDING,
            ]);
        } else {
            // Для гостей - валидация email и имени
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'name' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Проверяем, есть ли незакрытый чат для этого email
            $existingChat = SupportChat::where('guest_email', $request->email)
                ->notClosed()
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($existingChat) {
                $existingChat->load(['messages' => function($query) {
                    $query->orderBy('created_at', 'asc')->with(['user', 'attachments']);
                }, 'user', 'assignedAdmin']);
                
                return response()->json([
                    'success' => true,
                    'chat' => $existingChat,
                ]);
            }
            
            // Создаём новый чат для гостя
            $chat = SupportChat::create([
                'guest_email' => $request->email,
                'guest_name' => $request->name,
                'status' => SupportChat::STATUS_PENDING,
            ]);
        }
        
        $chat->load(['messages' => function($query) {
            $query->orderBy('created_at', 'asc')->with(['user', 'attachments']);
        }, 'user', 'assignedAdmin']);
        
        // Автоматическое приветственное сообщение - если это новый чат и включено
        $greetingEnabled = \App\Models\Option::get('support_chat_greeting_enabled', false);
        
        if ($greetingEnabled && $chat->messages()->count() === 0) {
            // Получаем язык пользователя из запроса или используем текущую локаль
            $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
            if (!in_array($locale, array_keys(config('langs')))) {
                $locale = app()->getLocale();
            }
            
            $greetingMessage = \App\Models\Option::get('support_chat_greeting_message_' . $locale, '');
            // Fallback на русский, если нет перевода
            if (empty($greetingMessage)) {
                $greetingMessage = \App\Models\Option::get('support_chat_greeting_message_ru', '');
            }
            
            if (!empty($greetingMessage)) {
                // Отправляем приветственное сообщение от бота
                $greeting = SupportMessage::create([
                    'support_chat_id' => $chat->id,
                    'user_id' => null,
                    'sender_type' => SupportMessage::SENDER_ADMIN,
                    'message' => trim($greetingMessage),
                    'is_read' => false,
                ]);
                
                // Перезагружаем сообщения
                $chat->load(['messages' => function($query) {
                    $query->orderBy('created_at', 'asc')->with(['user', 'attachments']);
                }, 'user', 'assignedAdmin']);
            }
        }
        
        return response()->json([
            'success' => true,
            'chat' => $chat,
        ]);
    }
    
    /**
     * Получить сообщения чата
     */
    public function getMessages(Request $request, $chatId)
    {
        $user = $request->user('sanctum');
        $chat = SupportChat::findOrFail($chatId);
        
        // Проверка доступа
        if ($user) {
            if ($chat->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'У вас нет доступа к этому чату'
                ], 403);
            }
        } else {
            // Для гостей проверяем email
            if (!$request->has('email') || $chat->guest_email !== $request->input('email')) {
                return response()->json([
                    'success' => false,
                    'message' => 'У вас нет доступа к этому чату'
                ], 403);
            }
        }
        
        $messages = $chat->messages()
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Отмечаем сообщения от админов как прочитанные (только если есть непрочитанные)
        $unreadAdminMessages = $chat->messages()
            ->where('sender_type', SupportMessage::SENDER_ADMIN)
            ->where('is_read', false);
            
        if ($unreadAdminMessages->exists()) {
            $unreadAdminMessages->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
        
        return response()->json([
            'success' => true,
            'messages' => $messages,
            'is_typing' => $chat->isTyping('admin'), // Добавляем статус печати в ответ
            'chat' => [
                'id' => $chat->id,
                'status' => $chat->status,
                'last_message_at' => $chat->last_message_at,
            ],
        ]);
    }
    
    /**
     * Отправить сообщение
     */
    public function sendMessage(Request $request, $chatId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:5', // Максимум 5 файлов
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip,rar|max:10240', // 10MB max per file
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Сообщение или файлы обязательны
        $messageText = trim($request->input('message', ''));
        if (empty($messageText) && !$request->hasFile('attachments')) {
            return response()->json([
                'success' => false,
                'errors' => ['message' => ['Необходимо указать сообщение или прикрепить файл.']],
            ], 422);
        }
        
        // Дополнительная валидация: проверка общего размера всех файлов (максимум 50MB)
        if ($request->hasFile('attachments')) {
            $totalSize = 0;
            $maxTotalSize = 50 * 1024 * 1024; // 50MB общий лимит
            
            foreach ($request->file('attachments') as $file) {
                $totalSize += $file->getSize();
            }
            
            if ($totalSize > $maxTotalSize) {
                return response()->json([
                    'success' => false,
                    'errors' => ['attachments' => ['Общий размер всех файлов не должен превышать 50MB']],
                ], 422);
            }
            
            // Проверка доступного места на диске (минимум 100MB должно остаться)
            $freeSpace = disk_free_space(storage_path('app'));
            if ($freeSpace !== false && $freeSpace < (100 * 1024 * 1024)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['attachments' => ['Недостаточно места на диске для сохранения файлов']],
                ], 422);
            }
        }
        
        $user = $request->user('sanctum');
        $chat = SupportChat::findOrFail($chatId);
        
        // Проверка доступа и статуса чата
        if ($chat->status === SupportChat::STATUS_CLOSED) {
            return response()->json([
                'success' => false,
                'message' => 'Этот чат закрыт'
            ], 403);
        }

        // ВАЖНО: Проверка общего объема вложений в чате (Storage DOS protection)
        $currentTotalSize = $chat->getTotalAttachmentsSize();
        $chatMaxTotalSize = 100 * 1024 * 1024; // 100MB лимит на чат
        
        if ($currentTotalSize > $chatMaxTotalSize) {
             return response()->json([
                'success' => false,
                'errors' => ['attachments' => ['Превышен лимит вложений для этого чата. Удалите старые файлы или обратитесь к администратору.']],
            ], 422);
        }
        
        if ($user) {
            if ($chat->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'У вас нет доступа к этому чату'
                ], 403);
            }
            $senderType = SupportMessage::SENDER_USER;
            $userId = $user->id;
        } else {
            // Для гостей
            if (!$request->has('email') || $chat->guest_email !== $request->input('email')) {
                return response()->json([
                    'success' => false,
                    'message' => 'У вас нет доступа к этому чату'
                ], 403);
            }
            $senderType = SupportMessage::SENDER_GUEST;
            $userId = null;
        }
        
        $message = SupportMessage::create([
            'support_chat_id' => $chat->id,
            'user_id' => $userId,
            'sender_type' => $senderType,
            'message' => trim($request->input('message', '')),
            'is_read' => false,
        ]);
        
        // Обработка вложений
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-chat/attachments', 'public');
                $url = Storage::url($path);
                
                SupportMessageAttachment::create([
                    'support_message_id' => $message->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_url' => $url,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }
        
        // Обновляем время последнего сообщения
        $chat->update([
            'last_message_at' => now(),
        ]);
        
        // Отправляем уведомление администраторам о новом сообщении (только если сообщение от пользователя/гостя, не от админа)
        if ($senderType !== SupportMessage::SENDER_ADMIN) {
            $userEmail = $user ? $user->email : ($chat->guest_email ?? 'Гость');
            $userName = $user ? $user->name : ($chat->guest_name ?? 'Гость');
            $messagePreview = mb_substr($message->message, 0, 100);
            if (mb_strlen($message->message) > 100) {
                $messagePreview .= '...';
            }
            
            \App\Services\NotifierService::send(
                'support_chat',
                'Новое сообщение в чате поддержки',
                "Пользователь {$userEmail} ({$userName}) написал в чате #{$chat->id}: {$messagePreview}",
                'info'
            );
        }
        
        // Очищаем кеш счетчика непрочитанных сообщений для админ-панели
        \Illuminate\Support\Facades\Cache::forget('support_chats_unread_count');
        
        $message->load(['user', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
    
    /**
     * Добавить рейтинг к чату (для пользователей)
     */
    public function addRating(Request $request, $chatId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'rating_comment' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = $request->user('sanctum');
        $chat = SupportChat::findOrFail($chatId);
        
        // Проверка доступа
        if ($user) {
            if ($chat->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
        } else {
            if (!$request->has('email') || $chat->guest_email !== $request->input('email')) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
        }
        
        // Проверяем, что чат закрыт
        if ($chat->status !== SupportChat::STATUS_CLOSED) {
            return response()->json([
                'success' => false,
                'message' => 'Оценить можно только закрытый чат'
            ], 403);
        }
        
        // Проверяем, что рейтинг еще не поставлен
        if ($chat->rating) {
            return response()->json([
                'success' => false,
                'message' => 'Рейтинг уже поставлен'
            ], 403);
        }
        
        $chat->update([
            'rating' => $request->rating,
            'rating_comment' => $request->rating_comment ? trim($request->rating_comment) : null,
            'rated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Рейтинг добавлен',
            'chat' => $chat,
        ]);
    }
    
    /**
     * Получить список чатов пользователя (только для авторизованных)
     */
    public function getChats(Request $request)
    {
        $user = $request->user('sanctum');
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация'
            ], 401);
        }
        
        $chats = SupportChat::where('user_id', $user->id)
            ->with(['lastMessage', 'assignedAdmin'])
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->get();
        
        return response()->json([
            'success' => true,
            'chats' => $chats,
        ]);
    }
    
    /**
     * Отправить событие "печатает"
     */
    public function sendTyping(Request $request, $chatId)
    {
        $user = $request->user('sanctum');
        $chat = SupportChat::findOrFail($chatId);
        
        // Проверка доступа
        if ($user) {
            if ($chat->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
            $chat->setTyping('user', $user->id);
        } else {
            if (!$request->has('email') || $chat->guest_email !== $request->input('email')) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
            $chat->setTyping('guest'); // Для гостей используем общий ключ guest для чата
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Остановить событие "печатает"
     */
    public function stopTyping(Request $request, $chatId)
    {
        $user = $request->user('sanctum');
        $chat = SupportChat::findOrFail($chatId);
        
        // Проверка доступа
        if ($user) {
            if ($chat->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
            $chat->stopTyping('user', $user->id);
        } else {
            if (!$request->has('email') || $chat->guest_email !== $request->input('email')) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
            $chat->stopTyping('guest');
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Получить статус печати (проверяем, печатает ли противоположная сторона)
     */
    public function getTypingStatus(Request $request, $chatId)
    {
        $user = $request->user('sanctum');
        $chat = SupportChat::findOrFail($chatId);
        
        // Проверка доступа
        if ($user) {
            if ($chat->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
        } else {
            if (!$request->has('email') || $chat->guest_email !== $request->input('email')) {
                return response()->json(['success' => false, 'message' => 'У вас нет доступа к этому чату'], 403);
            }
        }
        
        // Проверяем, печатает ли администратор (используем групповой ключ для O(1))
        return response()->json([
            'success' => true,
            'is_typing' => $chat->isTyping('admin'),
        ]);
    }
}
