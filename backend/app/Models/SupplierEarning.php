<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierEarning extends Model
{
    protected $table = 'supplier_earnings';

    protected $fillable = [
        'supplier_id',
        'purchase_id',
        'transaction_id',
        'amount',
        'status',
        'available_at',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Scope: earnings that are held but their available_at already passed (ready to become available)
     */
    public function scopeReadyToRelease($query)
    {
        return $query->where('status', 'held')->whereNotNull('available_at')->where('available_at', '<=', now());
    }

    /**
     * Scope: available to withdraw (status held & available_at <= now OR status = available)
     */
    public function scopeAvailable($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'available')
              ->orWhere(function($q2) {
                  $q2->where('status', 'held')->whereNotNull('available_at')->where('available_at', '<=', now());
              });
        });
    }

    /**
     * Отменить earning при возврате средств (reversal)
     * 
     * @param string|null $reason Причина отмены
     * @return bool Успешно ли отменено
     */
    public function reverse(?string $reason = null): bool
    {
        // Нельзя отменить уже отмененные или выведенные средства
        if ($this->status === 'reversed') {
            \Illuminate\Support\Facades\Log::warning('Attempt to reverse already reversed SupplierEarning', [
                'earning_id' => $this->id,
                'supplier_id' => $this->supplier_id,
                'transaction_id' => $this->transaction_id,
            ]);
            return false;
        }

        if ($this->status === 'withdrawn') {
            \Illuminate\Support\Facades\Log::error('Attempt to reverse withdrawn SupplierEarning - funds already withdrawn!', [
                'earning_id' => $this->id,
                'supplier_id' => $this->supplier_id,
                'transaction_id' => $this->transaction_id,
                'amount' => $this->amount,
                'processed_at' => $this->processed_at,
            ]);
            return false;
        }

        // Обновляем статус на reversed
        $this->update([
            'status' => 'reversed',
            'processed_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info('SupplierEarning reversed', [
            'earning_id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'transaction_id' => $this->transaction_id,
            'purchase_id' => $this->purchase_id,
            'amount' => $this->amount,
            'previous_status' => $this->getOriginal('status'),
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Частично отменить earning (если уже частично выведено)
     * 
     * @param float $amountToReverse Сумма для отмены
     * @param string|null $reason Причина отмены
     * @return bool Успешно ли отменено
     */
    public function partialReverse(float $amountToReverse, ?string $reason = null): bool
    {
        if ($amountToReverse <= 0 || $amountToReverse > $this->amount) {
            \Illuminate\Support\Facades\Log::error('Invalid amount for partial reverse', [
                'earning_id' => $this->id,
                'requested_amount' => $amountToReverse,
                'earning_amount' => $this->amount,
            ]);
            return false;
        }

        // Если списываем полностью - просто отменяем
        if (abs($amountToReverse - $this->amount) < 0.01) {
            return $this->reverse($reason);
        }

        // Если частично - создаем новую запись для остатка и отменяем текущую
        $remainingAmount = $this->amount - $amountToReverse;

        // Создаем новую запись для остатка
        self::create([
            'supplier_id' => $this->supplier_id,
            'purchase_id' => $this->purchase_id,
            'transaction_id' => $this->transaction_id,
            'amount' => $remainingAmount,
            'status' => $this->status,
            'available_at' => $this->available_at,
        ]);

        // Текущую запись отменяем
        $this->update([
            'amount' => $amountToReverse,
            'status' => 'reversed',
            'processed_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info('SupplierEarning partially reversed', [
            'earning_id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'transaction_id' => $this->transaction_id,
            'reversed_amount' => $amountToReverse,
            'remaining_amount' => $remainingAmount,
            'reason' => $reason,
        ]);

        return true;
    }
}
