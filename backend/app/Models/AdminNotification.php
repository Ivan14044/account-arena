<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'status',
        'message',
        'read'
    ];

    public function markAsRead()
    {
        $this->update(['read' => true]);
    }

    public function isRead()
    {
        return $this->read === true;
    }

    /**
     * Получить отформатированный заголовок без плейсхолдеров
     */
    public function getFormattedTitleAttribute(): string
    {
        $title = $this->title;
        
        // Если это ключ перевода, пытаемся перевести
        $translated = __($title);
        if ($translated !== $title) {
            $title = $translated;
        }
        
        // Убираем плейсхолдеры типа (:method)
        $title = preg_replace('/\s*\(:method\)/', '', $title);
        // Убираем другие плейсхолдеры типа :email, :name и т.д.
        $title = preg_replace('/:\w+/', '', $title);
        
        return trim($title);
    }

    /**
     * Получить отформатированное сообщение без плейсхолдеров
     */
    public function getFormattedMessageAttribute(): string
    {
        $message = $this->message;
        
        // Убираем все плейсхолдеры
        $message = preg_replace('/:\w+/', '', $message);
        // Очищаем лишние знаки препинания (двойные запятые, пробелы)
        $message = preg_replace('/,\s*,/', ',', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        
        return trim($message);
    }
}
