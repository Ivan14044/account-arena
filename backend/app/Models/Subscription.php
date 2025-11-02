<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'service_id',
        'is_trial',
        'is_auto_renew',
        'expiring_email_sent',
        'next_payment_at',
        'order_id',
        'payment_attempts',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_ENDED = 'ended';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'credit_card' => 'Credit Card',
            'crypto' => 'Crypto',
            default => '-',
        };
    }
}
