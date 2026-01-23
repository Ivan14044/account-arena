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

    const STATUS_ACTIVE = 'active';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_PENDING = 'pending';

    protected $fillable = [
        'name',
        'email',
        'password',
        'sub_data',
        'google_id',
        'telegram_id',
        'telegram_username',
        'avatar',
        'provider',
        'lang',
        'session_pid',
        'extension_settings',
        'trc20_wallet',
        'card_number_uah',
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
        'supplier_rating' => 'decimal:2',
        'rating_updated_at' => 'datetime',
        'is_supplier' => 'boolean',
        'supplier_hold_hours' => 'integer',
    ];

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
     * Get the user's subscriptions.
     * ПРИМЕЧАНИЕ: Модель Subscription удалена из проекта.
     * Метод оставлен для обратной совместимости, но возвращает пустую коллекцию.
     */
    public function subscriptions(): HasMany
    {
        // Возвращаем пустую связь (модель Subscription не существует)
        // Это предотвращает ошибки в старом коде
        return $this->hasMany(Purchase::class)->whereRaw('1 = 0'); // Всегда пустой результат
    }

    /**
     * Get the user's purchases (купленные товары).
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
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
     * Alias for supplierProducts to support legacy or alternative naming.
     */
    public function serviceAccounts(): HasMany
    {
        return $this->hasMany(ServiceAccount::class, 'supplier_id');
    }

    /**
     * Get the supplier's withdrawal requests.
     */
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'supplier_id');
    }

    /**
     * Get the user's product disputes (as customer).
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(ProductDispute::class, 'user_id');
    }

    /**
     * Get the supplier's product disputes.
     */
    public function supplierDisputes(): HasMany
    {
        return $this->hasMany(ProductDispute::class, 'supplier_id');
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

    /**
     * Рассчитать и обновить рейтинг поставщика
     * Рейтинг = Процент валидных товаров
     */
    public function calculateSupplierRating(): float
    {
        if (!$this->is_supplier) {
            return 0;
        }
        
        // Получить все продажи за последние 90 дней
        $period = now()->subDays(90);
        
        $totalSales = Transaction::whereHas('serviceAccount', function($q) {
            $q->where('supplier_id', $this->id);
        })
        ->where('created_at', '>=', $period)
        ->whereIn('status', ['completed', 'success'])
        ->count();
        
        // Если продаж меньше 10 - рейтинг 100% (новичок)
        if ($totalSales < 10) {
            $this->update(['supplier_rating' => 100.00, 'rating_updated_at' => now()]);
            return 100.00;
        }
        
        // Получить количество возвратов и замен
        $disputes = ProductDispute::forSupplier($this->id)
            ->where('created_at', '>=', $period)
            ->resolved()
            ->get();
        
        $refunds = $disputes->where('admin_decision', ProductDispute::DECISION_REFUND)->count();
        $replacements = $disputes->where('admin_decision', ProductDispute::DECISION_REPLACEMENT)->count();
        
        // Формула: Процент валидных товаров
        $invalidSales = $refunds + $replacements;
        $validSales = $totalSales - $invalidSales;
        $rating = ($validSales / $totalSales) * 100;
        
        // Округлить до 2 знаков и ограничить 0-100
        $rating = max(0, min(100, round($rating, 2)));
        
        // Обновить рейтинг
        $this->update([
            'supplier_rating' => $rating,
            'rating_updated_at' => now(),
        ]);
        
        return $rating;
    }

    /**
     * Получить уровень рейтинга поставщика
     */
    public function getRatingLevel(): array
    {
        $rating = $this->supplier_rating ?? 100;
        
        if ($rating >= 95) {
            return [
                'level' => 'excellent',
                'name' => 'Отличный',
                'name_en' => 'Excellent',
                'icon' => '🏆',
                'stars' => 5,
                'class' => 'success',
                'badge' => 'Топ продавец'
            ];
        } elseif ($rating >= 85) {
            return [
                'level' => 'good',
                'name' => 'Хороший',
                'name_en' => 'Good',
                'icon' => '💎',
                'stars' => 4,
                'class' => 'info',
                'badge' => 'Надежный'
            ];
        } elseif ($rating >= 70) {
            return [
                'level' => 'normal',
                'name' => 'Нормальный',
                'name_en' => 'Normal',
                'icon' => '✅',
                'stars' => 3,
                'class' => 'primary',
                'badge' => null
            ];
        } elseif ($rating >= 50) {
            return [
                'level' => 'low',
                'name' => 'Низкий',
                'name_en' => 'Low',
                'icon' => '⚠️',
                'stars' => 2,
                'class' => 'warning',
                'badge' => 'Требует улучшения'
            ];
        } else {
            return [
                'level' => 'critical',
                'name' => 'Критичный',
                'name_en' => 'Critical',
                'icon' => '🚫',
                'stars' => 1,
                'class' => 'danger',
                'badge' => 'Риск блокировки'
            ];
        }
    }

    /**
     * Получить детали рейтинга поставщика
     */
    public function getRatingDetails(): array
    {
        if (!$this->is_supplier) {
            return [];
        }
        
        $period = now()->subDays(90);
        
        $totalSales = Transaction::whereHas('serviceAccount', function($q) {
            $q->where('supplier_id', $this->id);
        })
        ->where('created_at', '>=', $period)
        ->whereIn('status', ['completed', 'success'])
        ->count();
        
        if ($totalSales == 0) {
            return [
                'total_sales' => 0,
                'valid_sales' => 0,
                'invalid_sales' => 0,
                'refunds' => 0,
                'replacements' => 0,
                'rejected_disputes' => 0,
                'valid_percent' => 100.00,
                'invalid_percent' => 0.00,
            ];
        }
        
        $disputes = ProductDispute::forSupplier($this->id)
            ->where('created_at', '>=', $period)
            ->get();
        
        $resolvedDisputes = $disputes->where('status', ProductDispute::STATUS_RESOLVED);
        $refunds = $resolvedDisputes->where('admin_decision', ProductDispute::DECISION_REFUND)->count();
        $replacements = $resolvedDisputes->where('admin_decision', ProductDispute::DECISION_REPLACEMENT)->count();
        $rejected = $disputes->where('admin_decision', ProductDispute::DECISION_REJECTED)->count();
        
        $invalidSales = $refunds + $replacements;
        $validSales = $totalSales - $invalidSales;
        
        return [
            'total_sales' => $totalSales,
            'valid_sales' => $validSales,
            'invalid_sales' => $invalidSales,
            'refunds' => $refunds,
            'replacements' => $replacements,
            'rejected_disputes' => $rejected,
            'valid_percent' => round(($validSales / $totalSales) * 100, 2),
            'invalid_percent' => round(($invalidSales / $totalSales) * 100, 2),
        ];
    }

    /**
     * Проверить, активна ли персональная скидка пользователя
     */
    public function hasActivePersonalDiscount(): bool
    {
        $discount = $this->personal_discount ?? 0;
        
        if ($discount <= 0) {
            return false;
        }
        
        // Проверяем срок действия скидки
        if ($this->personal_discount_expires_at) {
            return now()->lessThanOrEqualTo($this->personal_discount_expires_at);
        }
        
        // Если срок действия не указан, скидка действует бессрочно
        return true;
    }

    /**
     * Получить размер активной персональной скидки
     */
    public function getActivePersonalDiscount(): int
    {
        if ($this->hasActivePersonalDiscount()) {
            return (int)($this->personal_discount ?? 0);
        }
        
        return 0;
    }

    /**
     * Получить строковый статус пользователя
     */
    public function getStatus(): string
    {
        if ($this->is_blocked) {
            return self::STATUS_BLOCKED;
        }
        if ($this->is_pending) {
            return self::STATUS_PENDING;
        }
        return self::STATUS_ACTIVE;
    }
}
