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
        $title = $this->title ?? '';
        
        // Если пусто, возвращаем пустую строку
        if (empty($title)) {
            return '';
        }
        
        // Если это ключ перевода вида "notifier.xxx", переводим его
        if (strpos($title, 'notifier.') === 0) {
            $translated = __($title);
            if ($translated !== $title) {
                $title = $translated;
            }
        } else {
            // Пытаемся перевести через стандартную функцию
            $translated = __($title);
            if ($translated !== $title) {
                $title = $translated;
            } else {
                // Если перевод не найден, пытаемся найти соответствующий ключ
                // Расширенный маппинг английских текстов на ключи переводов
                // Проверяем по ключевым словам, а не точному совпадению
                if (stripos($title, 'purcha') !== false || stripos($title, 'purchase') !== false) {
                    $translated = __('notifier.new_product_purchase_title');
                    if ($translated !== 'notifier.new_product_purchase_title') {
                        $title = $translated;
                    }
                } elseif (stripos($title, 'user') !== false && stripos($title, 'new') !== false) {
                    $translated = __('notifier.new_user_title');
                    if ($translated !== 'notifier.new_user_title') {
                        $title = $translated;
                    }
                } elseif (stripos($title, 'payment') !== false && stripos($title, 'new') !== false) {
                    $translated = __('notifier.new_payment_title');
                    if ($translated !== 'notifier.new_payment_title') {
                        $title = $translated;
                    }
                }
            }
        }
        
        // Убираем плейсхолдеры типа (:method), (:Balance), (Balance) и т.д.
        $title = preg_replace('/\s*\(:?\w+\)/', '', $title);
        // Убираем другие плейсхолдеры типа :email, :name и т.д.
        $title = preg_replace('/:\w+/', '', $title);
        // Убираем пустые скобки
        $title = preg_replace('/\s*\(\)/', '', $title);
        // Убираем дублирование текста (если заголовок повторяется) - упрощенная версия
        $words = explode(' ', trim($title));
        if (count($words) > 2) {
            $half = ceil(count($words) / 2);
            $firstHalf = implode(' ', array_slice($words, 0, $half));
            $secondHalf = implode(' ', array_slice($words, $half));
            if (trim($firstHalf) === trim($secondHalf)) {
                $title = $firstHalf;
            }
        }
        
        return trim($title);
    }

    /**
     * Получить отформатированное сообщение без плейсхолдеров
     */
    public function getFormattedMessageAttribute(): string
    {
        $message = $this->message ?? '';
        
        // Если пусто, возвращаем пустую строку
        if (empty($message)) {
            return '';
        }
        
        // Если это ключ перевода вида "notifier.xxx", переводим его
        if (strpos($message, 'notifier.') === 0) {
            $translated = __($message);
            if ($translated !== $message) {
                $message = $translated;
            }
        } else {
            // Пытаемся перевести через стандартную функцию
            $translated = __($message);
            if ($translated !== $message) {
                $message = $translated;
            } else {
                // Если перевод не найден, пытаемся найти соответствующий ключ для сообщений
                // Проверяем по ключевым словам
                if (stripos($message, 'purcha') !== false || stripos($message, 'purchase') !== false) {
                    $translated = __('notifier.new_product_purchase_message');
                    if ($translated !== 'notifier.new_product_purchase_message') {
                        $message = $translated;
                    }
                } elseif (stripos($message, 'user') !== false && (stripos($message, 'new') !== false || stripos($message, 'registered') !== false)) {
                    $translated = __('notifier.new_user_message');
                    if ($translated !== 'notifier.new_user_message') {
                        $message = $translated;
                    }
                } elseif (stripos($message, 'payment') !== false && stripos($message, 'new') !== false) {
                    $translated = __('notifier.new_payment_message');
                    if ($translated !== 'notifier.new_payment_message') {
                        $message = $translated;
                    }
                }
            }
        }
        
        // Убираем все плейсхолдеры
        $message = preg_replace('/:\w+/', '', $message);
        // Убираем плейсхолдеры в скобках типа (Balance), (Monobank) и т.д.
        $message = preg_replace('/\s*\(:?\w+\)/', '', $message);
        // Убираем пустые скобки
        $message = preg_replace('/\s*\(\)/', '', $message);
        // Очищаем лишние знаки препинания (двойные запятые, пробелы)
        $message = preg_replace('/,\s*,/', ',', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        
        return trim($message);
    }
}
