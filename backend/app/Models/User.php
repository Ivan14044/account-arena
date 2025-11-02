<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_blocked',
        'is_admin',
        'is_supplier',
        'is_pending',
        'sub_data',
        'google_id',
        'telegram_id',
        'telegram_username',
        'avatar',
        'provider',
        'personal_discount',
        'personal_discount_expires_at',
        'lang',
        'session_pid',
        'extension_settings',
        'balance',
        'supplier_balance',
        'supplier_commission',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'sub_data' => 'array',
        'extension_settings' => 'array',
        'personal_discount_expires_at' => 'datetime',
        'balance' => 'decimal:2',
        'supplier_balance' => 'decimal:2',
        'supplier_commission' => 'decimal:2',
        'is_supplier' => 'boolean',
    ];

    /**
     * Get the user's subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's internal notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the supplier's notifications.
     */
    public function supplierNotifications(): HasMany
    {
        return $this->hasMany(SupplierNotification::class);
    }

    /**
     * Get the user's vouchers.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Get the user's transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the supplier's products.
     */
    public function supplierProducts(): HasMany
    {
        return $this->hasMany(ServiceAccount::class, 'supplier_id');
    }

    /**
     * Get all unique active service IDs for the user.
     *
     * @return array<int>
     */
    public function activeServices(): array
    {
        return $this->subscriptions
            ->where('status', Subscription::STATUS_ACTIVE)
            ->pluck('service_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Check if the user has an active subscription for a given service.
     *
     * @param int $serviceId
     * @return bool
     */
    public function hasActiveService(int $serviceId): bool
    {
        return $this->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('service_id', $serviceId)
            ->exists();
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token, $this));
    }
}
