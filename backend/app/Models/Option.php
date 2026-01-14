<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * Поля, которые должны быть зашифрованы в БД
     */
    protected static $encryptedFields = [
        'smtp_password',
        'telegram_bot_token',
        'cryptomus_payment_key',
        'cryptomus_payout_key',
        'monobank_token',
    ];

    public static function set(string $name, $value): self
    {
        $value = is_array($value) ? json_encode($value) : $value;

        // Шифруем чувствительные данные
        if (in_array($name, self::$encryptedFields) && !empty($value)) {
            $value = encrypt($value);
        }

        $option = self::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );

        // Инвалидируем кеш
        \Illuminate\Support\Facades\Cache::forget('site_options_all');

        return $option;
    }

    public static function get(string $name, $defaultValue = null)
    {
        // Используем кеширование всех опций для минимизации запросов к БД
        $options = \Illuminate\Support\Facades\Cache::remember('site_options_all', 3600, function() {
            return self::pluck('value', 'name')->toArray();
        });

        $value = $options[$name] ?? $defaultValue;

        // Расшифровываем чувствительные данные
        if (in_array($name, self::$encryptedFields) && !empty($value)) {
            try {
                $value = decrypt($value);
            } catch (\Exception $e) {
                // Если данные не зашифрованы (старые записи), возвращаем как есть
                \Illuminate\Support\Facades\Log::warning("Failed to decrypt option: {$name}");
            }
        }

        return $value;
    }
}
