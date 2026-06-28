<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Статусы транзакции. Колонка transactions.status — обычная строка
     * (default 'completed', миграция 2025_11_02_092101). Это статус НАШЕЙ
     * транзакции; статус платёжного шлюза ('success' у Monobank) — отдельное
     * понятие и константой здесь не является.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'guest_email', // Email для гостевых транзакций
        'amount',
        'currency',
        'payment_method',
        'service_account_id',
        'status',
        'metadata', // Дополнительные метаданные транзакции (JSON)
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array', // Автоматическое преобразование JSON в массив и обратно
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceAccount()
    {
        return $this->belongsTo(ServiceAccount::class);
    }

    public function dispute()
    {
        return $this->hasOne(ProductDispute::class);
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }
}
