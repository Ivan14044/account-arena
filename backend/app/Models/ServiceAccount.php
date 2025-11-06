<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ServiceAccount extends Model
{
    use HasFactory;

    /**
     * Boot метод для автоматической генерации артикула
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            // Генерируем артикул только если он не задан
            if (empty($account->sku)) {
                $account->sku = self::generateSku();
            }
        });
    }

    protected $fillable = [
        'service_id',
        'category_id',
        'supplier_id',
        'profile_id',
        'credentials',
        'used',
        'expiring_at',
        'last_used_at',
        'is_active',
        'price',
        'discount_percent',
        'discount_start_date',
        'discount_end_date',
        'title',
        'description',
        'title_en',
        'description_en',
        'title_uk',
        'description_uk',
        'image_url',
        'additional_description',
        'additional_description_en',
        'additional_description_uk',
        'meta_title',
        'meta_title_en',
        'meta_title_uk',
        'meta_description',
        'meta_description_en',
        'meta_description_uk',
        'show_only_telegram',
        'accounts_data',
        'sku', // Артикул товара
    ];

    protected $casts = [
        'credentials' => 'array',
        'accounts_data' => 'array',
        'expiring_at' => 'datetime',
        'last_used_at' => 'datetime',
        'discount_start_date' => 'datetime',
        'discount_end_date' => 'datetime',
        'is_active' => 'boolean',
        'show_only_telegram' => 'boolean',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id')->withDefault([
            'name' => 'Администратор',
            'email' => 'admin',
            'is_supplier' => false,
        ]);
    }

    /**
     * Проверить, принадлежит ли товар администратору
     */
    public function isAdminProduct()
    {
        return $this->supplier_id === null;
    }

    /**
     * Получить имя владельца товара
     */
    public function getOwnerName()
    {
        return $this->supplier_id ? $this->supplier->name : 'Администратор';
    }

    /**
     * Get the current price with discount applied if active
     */
    public function getCurrentPrice()
    {
        if ($this->hasActiveDiscount()) {
            $discount = ($this->price * $this->discount_percent) / 100;
            return round($this->price - $discount, 2);
        }
        return $this->price;
    }

    /**
     * Check if product has an active discount
     */
    public function hasActiveDiscount()
    {
        if (!$this->discount_percent || $this->discount_percent <= 0) {
            return false;
        }

        $now = now();
        $startValid = !$this->discount_start_date || $now->greaterThanOrEqualTo($this->discount_start_date);
        $endValid = !$this->discount_end_date || $now->lessThanOrEqualTo($this->discount_end_date);

        return $startValid && $endValid;
    }

    /**
     * Get available stock quantity
     */
    public function getAvailableStock()
    {
        $accountsData = $this->accounts_data ?? [];
        $totalQty = is_array($accountsData) ? count($accountsData) : 0;
        $used = $this->used ?? 0;
        return max(0, $totalQty - $used);
    }

    /**
     * Check if stock is low (less than 10)
     */
    public function isLowStock()
    {
        return $this->getAvailableStock() < 10;
    }

    /**
     * Генерация уникального артикула (SKU)
     * Формат: PRD-XXXXXX-YYYY
     * где XXXXXX - 6 цифр (время + случайность)
     *     YYYY - 4 случайных буквы/цифры
     */
    public static function generateSku(): string
    {
        do {
            // Генерируем артикул: PRD-timestamp(6)-random(4)
            $timestamp = substr(time(), -6); // Последние 6 цифр timestamp
            $random = strtoupper(Str::random(4)); // 4 случайных символа
            $sku = "PRD-{$timestamp}-{$random}";
            
            // Проверяем уникальность
            $exists = self::where('sku', $sku)->exists();
        } while ($exists);
        
        return $sku;
    }
}
