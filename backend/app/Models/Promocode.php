<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'prefix',
        'batch_id',
        'percent_discount',
        'usage_limit',
        'per_user_limit',
        'usage_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['free_days'])
            ->withTimestamps();
    }

    public function isUnlimited(): bool
    {
        return (int) $this->usage_limit === 0;
    }

    public function canBeUsed(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if (!$this->isUnlimited() && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}


