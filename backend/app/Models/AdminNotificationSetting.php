<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'registration_enabled',
        'product_purchase_enabled',
        'dispute_created_enabled',
        'payment_enabled',
        'topup_enabled',
        'support_chat_enabled',
        'sound_enabled',
    ];

    protected $casts = [
        'registration_enabled' => 'boolean',
        'product_purchase_enabled' => 'boolean',
        'dispute_created_enabled' => 'boolean',
        'payment_enabled' => 'boolean',
        'topup_enabled' => 'boolean',
        'support_chat_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
    ];

    /**
     * Связь с пользователем (администратором)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить или создать настройки для пользователя
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'registration_enabled' => true,
                'product_purchase_enabled' => true,
                'dispute_created_enabled' => true,
                'payment_enabled' => true,
                'topup_enabled' => true,
                'support_chat_enabled' => true,
                'sound_enabled' => true,
            ]
        );
    }

    /**
     * Проверить, включено ли уведомление определенного типа
     */
    public function isEnabled(string $type): bool
    {
        $field = $this->getFieldName($type);
        return $this->$field ?? true; // По умолчанию включено
    }

    /**
     * Получить имя поля для типа уведомления
     */
    private function getFieldName(string $type): string
    {
        $mapping = [
            'registration' => 'registration_enabled',
            'product_purchase' => 'product_purchase_enabled',
            'dispute_created' => 'dispute_created_enabled',
            'payment' => 'payment_enabled',
            'topup' => 'topup_enabled',
            'support_chat' => 'support_chat_enabled',
        ];

        return $mapping[$type] ?? 'product_purchase_enabled';
    }
}
