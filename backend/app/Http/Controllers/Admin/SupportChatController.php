<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportChatNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportChatController extends Controller
{
    /**
     * Список всех чатов
     */
    public function index(Request $request)
    {
        $query = SupportChat::with(['user', 'assignedAdmin', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc');
        
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
        $chat = SupportChat::with(['user', 'assignedAdmin', 'messages.user', 'messages.attachments'])
            ->findOrFail($id);
        
        // Отмечаем сообщения от пользователей/гостей как прочитанные
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
        
        // Очищаем кеш счетчика непрочитанных сообщений
        \Illuminate\Support\Facades\Cache::forget('support_chats_unread_count');
        
        // Загружаем заметки администраторов
        $chat->load('notes.user');
        
        return view('admin.support-chats.show', compact('chat'));
    }
    
    /**
     * Отправить сообщение от администратора
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5', // Максимум 5 файлов
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip,rar|max:10240', // 10MB max per file
        ]);
        
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();
        
        $message = SupportMessage::create([
            'support_chat_id' => $chat->id,
            'user_id' => $admin->id,
            'sender_type' => SupportMessage::SENDER_ADMIN,
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
        
        // Останавливаем индикатор печати
        $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
        \Illuminate\Support\Facades\Cache::forget($key);
        
        // Обновляем время последнего сообщения
        $chat->update([
            'last_message_at' => now(),
            'status' => SupportChat::STATUS_OPEN,
        ]);
        
        // Очищаем кеш счетчика непрочитанных сообщений
        \Illuminate\Support\Facades\Cache::forget('support_chats_unread_count');
        
        return redirect()->back()->with('success', 'Сообщение отправлено');
    }
    
    /**
     * Отправить событие "печатает" от администратора
     */
    public function sendTyping(Request $request, $id)
    {
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();
        
        $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
        \Illuminate\Support\Facades\Cache::put($key, true, 5); // 5 секунд
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Остановить событие "печатает" от администратора
     */
    public function stopTyping(Request $request, $id)
    {
        $chat = SupportChat::findOrFail($id);
        $admin = $request->user();
        
        $key = 'support_chat_typing_' . $chat->id . '_admin_' . $admin->id;
        \Illuminate\Support\Facades\Cache::forget($key);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Получить статус печати пользователя
     */
    public function getUserTypingStatus($id)
    {
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
        
        return response()->json([
            'success' => true,
            'is_typing' => $isTyping,
        ]);
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
        
        // Очищаем кеш счетчика непрочитанных сообщений
        \Illuminate\Support\Facades\Cache::forget('support_chats_unread_count');
        
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
        $unreadCount = SupportMessage::whereHas('chat', function($query) {
            $query->where('status', '!=', SupportChat::STATUS_CLOSED);
        })
        ->fromUserOrGuest()
        ->where('is_read', false)
        ->count();
        
        return response()->json(['count' => $unreadCount]);
    }
}
