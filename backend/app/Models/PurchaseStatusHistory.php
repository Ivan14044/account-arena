<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'purchase_status_history';

    protected $fillable = [
        'purchase_id',
        'old_status',
        'new_status',
        'changed_by',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Связь с заказом
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Связь с пользователем/администратором, который изменил статус
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Создать запись об изменении статуса
     * 
     * @param Purchase $purchase
     * @param string $newStatus
     * @param string|null $oldStatus
     * @param User|null $changedBy
     * @param string|null $reason
     * @param array|null $metadata
     * @return PurchaseStatusHistory
     */
    public static function createHistory(
        Purchase $purchase,
        string $newStatus,
        ?string $oldStatus = null,
        ?User $changedBy = null,
        ?string $reason = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'purchase_id' => $purchase->id,
            'old_status' => $oldStatus ?? $purchase->status,
            'new_status' => $newStatus,
            'changed_by' => $changedBy?->id,
            'reason' => $reason,
            'metadata' => $metadata ?? [
                'account_data_count' => is_array($purchase->account_data) ? count($purchase->account_data) : 0,
                'quantity' => $purchase->quantity,
                'total_amount' => $purchase->total_amount,
            ],
        ]);
    }
}
