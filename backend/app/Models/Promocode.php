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

    // Services are no longer supported - this relationship has been removed

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

    /**
     * Проверка возможности использования промокода конкретным пользователем
     */
    public function canUserUse(?User $user): bool
    {
        if (!$user) {
            return true; // Для гостей ограничение per_user_limit не применяется здесь (обычно через сессии или IP в другом месте)
        }

        $usageCount = \Illuminate\Support\Facades\DB::table('promocode_usages')
            ->where('promocode_id', $this->id)
            ->where('user_id', $user->id)
            ->count();

        return $usageCount < ($this->per_user_limit ?: 1);
    }
}


