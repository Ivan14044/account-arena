<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для хранения истории операций с балансом пользователя
 * 
 * Каждая запись представляет собой одну операцию изменения баланса:
 * - Пополнение (топ-ап)
 * - Списание за покупку
 * - Возврат средств
 * - Корректировка администратором
 */
class BalanceTransaction extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     */
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'description',
        'metadata',
    ];

    /**
     * Преобразование типов атрибутов
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Типы операций с балансом
     */
    const TYPE_TOPUP_CARD = 'topup_card';           // Пополнение картой
    const TYPE_TOPUP_CRYPTO = 'topup_crypto';       // Пополнение криптовалютой
    const TYPE_TOPUP_ADMIN = 'topup_admin';         // Пополнение администратором
    const TYPE_TOPUP_VOUCHER = 'topup_voucher';     // Пополнение ваучером
    const TYPE_DEDUCTION = 'deduction';             // Списание средств
    const TYPE_REFUND = 'refund';                   // Возврат средств
    const TYPE_PURCHASE = 'purchase';               // Покупка товара
    const TYPE_ADJUSTMENT = 'adjustment';           // Корректировка администратором
    
    /**
     * Статусы операций
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверить, является ли операция пополнением
     */
    public function isTopUp(): bool
    {
        return in_array($this->type, [
            self::TYPE_TOPUP_CARD,
            self::TYPE_TOPUP_CRYPTO,
            self::TYPE_TOPUP_ADMIN,
            self::TYPE_TOPUP_VOUCHER,
        ]);
    }

    /**
     * Проверить, является ли операция списанием
     */
    public function isDeduction(): bool
    {
        return in_array($this->type, [
            self::TYPE_DEDUCTION,
            self::TYPE_PURCHASE,
        ]);
    }

    /**
     * Проверить, является ли операция возвратом
     */
    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Получить форматированную сумму с валютой
     */
    public function getFormattedAmountAttribute(): string
    {
        $currency = Option::get('currency', 'USD');
        $sign = $this->amount >= 0 ? '+' : '';
        return $sign . number_format($this->amount, 2, '.', '') . ' ' . strtoupper($currency);
    }

    /**
     * Получить читаемое название типа операции
     */
    public function getTypeNameAttribute(): string
    {
        $types = [
            self::TYPE_TOPUP_CARD => 'Пополнение картой',
            self::TYPE_TOPUP_CRYPTO => 'Пополнение криптовалютой',
            self::TYPE_TOPUP_ADMIN => 'Пополнение администратором',
            self::TYPE_TOPUP_VOUCHER => 'Пополнение ваучером',
            self::TYPE_DEDUCTION => 'Списание',
            self::TYPE_REFUND => 'Возврат средств',
            self::TYPE_PURCHASE => 'Оплата покупки',
            self::TYPE_ADJUSTMENT => 'Корректировка',
        ];

        return $types[$this->type] ?? 'Неизвестная операция';
    }

    /**
     * Получить читаемое название статуса
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Ожидание',
            self::STATUS_COMPLETED => 'Завершено',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_CANCELLED => 'Отменено',
        ];

        return $statuses[$this->status] ?? 'Неизвестный статус';
    }

    /**
     * Scope для фильтрации по типу операции
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope для фильтрации по статусу
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для получения только пополнений
     */
    public function scopeTopUps($query)
    {
        return $query->whereIn('type', [
            self::TYPE_TOPUP_CARD,
            self::TYPE_TOPUP_CRYPTO,
            self::TYPE_TOPUP_ADMIN,
            self::TYPE_TOPUP_VOUCHER,
        ]);
    }

    /**
     * Scope для получения только списаний
     */
    public function scopeDeductions($query)
    {
        return $query->whereIn('type', [
            self::TYPE_DEDUCTION,
            self::TYPE_PURCHASE,
        ]);
    }

    /**
     * Scope для получения завершенных операций
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}


