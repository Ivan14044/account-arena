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
}
