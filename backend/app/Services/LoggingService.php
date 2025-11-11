<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для структурированного логирования с защитой PII
 */
class LoggingService
{
    /**
     * Поля, которые содержат чувствительные данные
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
        'card_number',
        'account_data',
        'accounts_data',
    ];

    /**
     * Поля с PII (Personal Identifiable Information)
     */
    private const PII_FIELDS = [
        'email',
        'phone',
        'name',
        'address',
        'ip',
        'payment_details',
    ];

    /**
     * Логирование с sanitization
     */
    public static function info(string $message, array $context = []): void
    {
        $sanitized = self::sanitize($context);
        Log::info($message, $sanitized);
    }

    /**
     * Логирование ошибки с sanitization
     */
    public static function error(string $message, array $context = []): void
    {
        $sanitized = self::sanitize($context);
        Log::error($message, $sanitized);
    }

    /**
     * Логирование warning с sanitization
     */
    public static function warning(string $message, array $context = []): void
    {
        $sanitized = self::sanitize($context);
        Log::warning($message, $sanitized);
    }

    /**
     * Очистка чувствительных данных из контекста
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (self::isSensitiveField($key)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (self::isPiiField($key)) {
                $sanitized[$key] = self::maskPii($key, $value);
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Проверка является ли поле чувствительным
     */
    private static function isSensitiveField(string $key): bool
    {
        $lowerKey = strtolower($key);
        
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (Str::contains($lowerKey, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка является ли поле PII
     */
    private static function isPiiField(string $key): bool
    {
        $lowerKey = strtolower($key);
        
        foreach (self::PII_FIELDS as $field) {
            if (Str::contains($lowerKey, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Маскировка PII данных
     */
    private static function maskPii(string $key, mixed $value): string
    {
        if (!is_string($value)) {
            return '[MASKED]';
        }

        // Email: show first 2 chars + domain
        if (Str::contains(strtolower($key), 'email') && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $value);
            $local = $parts[0];
            $domain = $parts[1] ?? '';
            
            $masked = Str::substr($local, 0, 2) . '***';
            return $domain ? $masked . '@' . $domain : $masked;
        }

        // IP: show first 2 octets
        if (Str::contains(strtolower($key), 'ip') && filter_var($value, FILTER_VALIDATE_IP)) {
            $parts = explode('.', $value);
            return ($parts[0] ?? '***') . '.' . ($parts[1] ?? '***') . '.***.***.';
        }

        // Остальное: показываем первые 3 символа
        return Str::substr($value, 0, 3) . '***';
    }

    /**
     * Логирование с request context
     */
    public static function logWithRequest(string $level, string $message, array $context = []): void
    {
        $request = request();
        
        $enriched = array_merge([
            'request_id' => $request->header('X-Request-ID'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ], $context);

        $sanitized = self::sanitize($enriched);

        Log::log($level, $message, $sanitized);
    }
}



