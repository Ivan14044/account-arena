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
    ];

    protected $casts = [
        'account_data' => 'array',
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2',
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
}
