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
        
        // Нормализуем текст - убираем лишние пробелы
        $title = trim($title);
        $originalTitle = $title;
        
        // Сначала проверяем по ключевым словам (более агрессивный подход)
        // Делаем это ДО удаления плейсхолдеров, чтобы гарантировать перевод
        // Убираем пробелы для более надежного поиска
        $titleLower = mb_strtolower(str_replace(' ', '', $title));
        $titleLowerWithSpaces = mb_strtolower($title);
        $wasTranslated = false;
        
        if (strpos($titleLower, 'purcha') !== false || strpos($titleLowerWithSpaces, 'purchase') !== false || strpos($titleLowerWithSpaces, 'purcha') !== false) {
            // Это похоже на уведомление о покупке
            $translated = __('notifier.new_product_purchase_title');
            if ($translated !== 'notifier.new_product_purchase_title') {
                $title = $translated;
                $wasTranslated = true;
            }
        } elseif (strpos($titleLower, 'user') !== false && strpos($titleLower, 'new') !== false) {
            // Это похоже на уведомление о новом пользователе
            $translated = __('notifier.new_user_title');
            if ($translated !== 'notifier.new_user_title') {
                $title = $translated;
                $wasTranslated = true;
            }
        } elseif (strpos($titleLower, 'payment') !== false && strpos($titleLower, 'new') !== false) {
            // Это похоже на уведомление о платеже
            $translated = __('notifier.new_payment_title');
            if ($translated !== 'notifier.new_payment_title') {
                $title = $translated;
                $wasTranslated = true;
            }
        } elseif (strpos($title, 'notifier.') === 0) {
            // Если это ключ перевода вида "notifier.xxx", переводим его
            $translated = __($title, [], 'ru'); // Принудительно используем русскую локаль
            if ($translated !== $title) {
                $title = $translated;
                $wasTranslated = true;
            }
        } else {
            // Пытаемся перевести через стандартную функцию
            $translated = __($title);
            if ($translated !== $title) {
                $title = $translated;
                $wasTranslated = true;
            }
            // Если перевод вернул английский текст (старые записи), пытаемся перевести на русский
            if ($wasTranslated && preg_match('/^[A-Za-z\s]+$/', $title) && strpos($originalTitle, 'notifier.') === 0) {
                $translated = __($originalTitle, [], 'ru');
                if ($translated !== $originalTitle) {
                    $title = $translated;
                }
            }
        }
        
        // Если перевод не произошел, но в оригинале есть ключевые слова, пытаемся еще раз
        if (!$wasTranslated) {
            $originalLower = mb_strtolower($originalTitle);
            $originalLowerNoSpaces = mb_strtolower(str_replace(' ', '', $originalTitle));
            if (strpos($originalLower, 'purcha') !== false || strpos($originalLowerNoSpaces, 'purcha') !== false) {
                $title = __('notifier.new_product_purchase_title');
                $wasTranslated = true;
            }
        }
        
        // Убираем плейсхолдеры типа (:method), (:Balance), (Balance) и т.д.
        $title = preg_replace('/\s*\(:?\w+\)/', '', $title);
        // Убираем другие плейсхолдеры типа :email, :name, :products, :amount и т.д.
        $title = preg_replace('/:\w+/', '', $title);
        // Убираем оставшиеся плейсхолдеры после запятых (например, ", products:")
        $title = preg_replace('/,\s*\w+\s*:/', '', $title);
        // Убираем пустые скобки
        $title = preg_replace('/\s*\(\)/', '', $title);
        // Убираем запятые с пустыми значениями (например, "email: , name: ,")
        $title = preg_replace('/,\s*email:\s*,/', ',', $title);
        $title = preg_replace('/,\s*name:\s*,/', ',', $title);
        $title = preg_replace('/,\s*product\s*:\s*,/', ',', $title);
        $title = preg_replace('/,\s*amount:/', '', $title);
        // Убираем дублирование текста (если заголовок повторяется)
        // Проверяем, не начинается ли текст с повторения первых слов
        $words = explode(' ', trim($title));
        if (count($words) >= 4) {
            // Берем первые 2-3 слова и проверяем, не повторяются ли они
            $firstPart = implode(' ', array_slice($words, 0, min(3, floor(count($words) / 2))));
            $rest = implode(' ', array_slice($words, min(3, floor(count($words) / 2))));
            if (strpos($rest, $firstPart) === 0) {
                $title = $rest;
            }
        }
        // Если после обработки остались только пустые значения, пытаемся перевести еще раз
        if (empty(trim($title)) || preg_match('/^[\s,:\-]+$/', $title)) {
            // Если текст стал пустым после удаления плейсхолдеров, используем оригинальный заголовок
            $originalTitle = $this->title ?? '';
            $titleLower = mb_strtolower($originalTitle);
            if (strpos($titleLower, 'purcha') !== false || strpos($titleLower, 'purchase') !== false) {
                $title = __('notifier.new_product_purchase_title');
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
        
        // Нормализуем текст - убираем лишние пробелы
        $message = trim($message);
        
        // Сначала проверяем по ключевым словам (более агрессивный подход)
        // Убираем пробелы для более надежного поиска
        $messageLower = mb_strtolower(str_replace(' ', '', $message));
        $messageLowerWithSpaces = mb_strtolower($message);
        if (strpos($messageLower, 'purcha') !== false || strpos($messageLowerWithSpaces, 'purchase') !== false || strpos($messageLowerWithSpaces, 'purcha') !== false) {
            // Это похоже на сообщение о покупке
            $translated = __('notifier.new_product_purchase_message');
            if ($translated !== 'notifier.new_product_purchase_message') {
                $message = $translated;
            }
        } elseif (strpos($messageLower, 'user') !== false && (strpos($messageLower, 'new') !== false || strpos($messageLower, 'registered') !== false)) {
            // Это похоже на сообщение о новом пользователе
            $translated = __('notifier.new_user_message');
            if ($translated !== 'notifier.new_user_message') {
                $message = $translated;
            }
        } elseif (strpos($messageLower, 'payment') !== false && strpos($messageLower, 'new') !== false) {
            // Это похоже на сообщение о платеже
            $translated = __('notifier.new_payment_message');
            if ($translated !== 'notifier.new_payment_message') {
                $message = $translated;
            }
        } elseif (strpos($message, 'notifier.') === 0) {
            // Если это ключ перевода вида "notifier.xxx", переводим его
            $translated = __($message, [], 'ru'); // Принудительно используем русскую локаль
            if ($translated !== $message) {
                $message = $translated;
            }
        } else {
            // Пытаемся перевести через стандартную функцию
            $translated = __($message);
            if ($translated !== $message) {
                $message = $translated;
            }
            // Если перевод вернул английский текст (старые записи), пытаемся перевести на русский
            $originalMessage = $this->message ?? '';
            if (preg_match('/^[A-Za-z\s,:\-]+$/', $message) && strpos($originalMessage, 'notifier.') === 0) {
                $translated = __($originalMessage, [], 'ru');
                if ($translated !== $originalMessage) {
                    $message = $translated;
                }
            }
        }
        
        // Убираем все плейсхолдеры типа :email, :name, :products, :amount и т.д.
        $message = preg_replace('/:\w+/', '', $message);
        // Убираем плейсхолдеры в скобках типа (Balance), (Monobank) и т.д.
        $message = preg_replace('/\s*\(:?\w+\)/', '', $message);
        // Убираем пустые скобки
        $message = preg_replace('/\s*\(\)/', '', $message);
        // Убираем оставшиеся плейсхолдеры после запятых (например, ", products:", ", email:")
        $message = preg_replace('/,\s*\w+\s*:/', '', $message);
        // Убираем запятые с пустыми значениями
        $message = preg_replace('/,\s*email:\s*,/', ',', $message);
        $message = preg_replace('/,\s*name:\s*,/', ',', $message);
        $message = preg_replace('/,\s*product\s*:\s*,/', ',', $message);
        $message = preg_replace('/,\s*amount:/', '', $message);
        // Очищаем лишние знаки препинания (двойные запятые, пробелы)
        $message = preg_replace('/,\s*,/', ',', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        // Убираем дублирование текста в сообщениях
        $words = explode(' ', trim($message));
        if (count($words) >= 4) {
            $firstPart = implode(' ', array_slice($words, 0, min(3, floor(count($words) / 2))));
            $rest = implode(' ', array_slice($words, min(3, floor(count($words) / 2))));
            if (strpos($rest, $firstPart) === 0) {
                $message = $rest;
            }
        }
        
        return trim($message);
    }
}
