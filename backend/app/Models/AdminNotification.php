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
                $keyMapping = [
                    'New purchase' => 'notifier.new_product_purchase_title',
                    'New Purchase' => 'notifier.new_product_purchase_title',
                    'New user' => 'notifier.new_user_title',
                    'New User' => 'notifier.new_user_title',
                    'New payment' => 'notifier.new_payment_title',
                    'New Payment' => 'notifier.new_payment_title',
                    'Product Purchase' => 'notifier.types.product_purchase',
                ];
                
                // Попытка найти по частичному совпадению (без учета регистра)
                foreach ($keyMapping as $english => $key) {
                    if (stripos($title, $english) !== false) {
                        $translated = __($key);
                        if ($translated !== $key) {
                            $title = $translated;
                            break;
                        }
                    }
                }
            }
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
                $keyMapping = [
                    'New purchase' => 'notifier.new_product_purchase_message',
                    'New Purchase' => 'notifier.new_product_purchase_message',
                    'New user registered' => 'notifier.new_user_message',
                    'New user' => 'notifier.new_user_message',
                    'New payment' => 'notifier.new_payment_message',
                    'New Payment' => 'notifier.new_payment_message',
                ];
                
                // Попытка найти по частичному совпадению
                foreach ($keyMapping as $english => $key) {
                    if (stripos($message, $english) !== false) {
                        $translated = __($key);
                        if ($translated !== $key) {
                            $message = $translated;
                            break;
                        }
                    }
                }
            }
        }
        
        // Убираем все плейсхолдеры
        $message = preg_replace('/:\w+/', '', $message);
        // Очищаем лишние знаки препинания (двойные запятые, пробелы)
        $message = preg_replace('/,\s*,/', ',', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        
        return trim($message);
    }
}
