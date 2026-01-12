<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email', // Email для гостевых покупок
        'service_account_id',
        'transaction_id',
        'quantity',
        'price',
        'total_amount',
        'account_data',
        'status',
        'processed_by', // ID администратора, который обработал заказ
        'processed_at', // Дата и время обработки
        'processing_notes', // Заметки менеджера
        'admin_notes', // Внутренние заметки администратора
    ];

    protected $casts = [
        'account_data' => 'array',
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Связь с пользователем
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Связь с товаром
    public function serviceAccount()
    {
        return $this->belongsTo(ServiceAccount::class);
    }

    // Связь с транзакцией
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Связь с администратором, который обработал заказ
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Генерация уникального номера заказа
     * Формат: ORD-YYYYMMDD-XXXXX (например: ORD-20251104-12345)
     */
    public static function generateOrderNumber(): string
    {
        do {
            // Генерируем номер: ORD-дата-случайное_число
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Проверяем уникальность
            $exists = self::where('order_number', $orderNumber)->exists();
        } while ($exists);

        return $orderNumber;
    }

    /**
     * Константы для статусов заказа
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Проверить, находится ли заказ в обработке
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Проверить, завершен ли заказ
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, требует ли заказ ручной обработки
     */
    public function requiresManualProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING 
            && $this->serviceAccount 
            && $this->serviceAccount->requiresManualDelivery();
    }

    /**
     * Scope: заказы, ожидающие ручной обработки
     */
    public function scopePendingManualProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING)
            ->whereHas('serviceAccount', function($q) {
                $q->where('delivery_type', \App\Models\ServiceAccount::DELIVERY_MANUAL);
            });
    }
}
