<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
            
            // Устанавливаем sort_order для новых товаров (в конец списка)
            if (empty($account->sort_order)) {
                $maxSortOrder = self::max('sort_order') ?? 0;
                $account->sort_order = $maxSortOrder + 1;
            }
        });
    }

    protected $fillable = [
        'sort_order', // Порядок сортировки товаров
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
        'seo_text',
        'seo_text_en',
        'seo_text_uk',
        'instruction',
        'instruction_en',
        'instruction_uk',
        'show_only_telegram',
        'accounts_data',
        'account_suffix_enabled',
        'account_suffix_text_ru',
        'account_suffix_text_en',
        'account_suffix_text_uk',
        'sku', // Артикул товара
        'moderation_status', // Статус модерации
        'moderation_comment', // Комментарий администратора
        'moderated_at', // Дата модерации
        'moderated_by', // ID администратора, который провел модерацию
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
        'moderated_at' => 'datetime',
    ];

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
     * Администратор, который провел модерацию
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
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
     * ВАЖНО: Для товаров поставщика использует getPriceWithCommission()
     */
    public function getCurrentPrice()
    {
        // Для товаров поставщика применяем комиссию
        if ($this->supplier_id) {
            return $this->getPriceWithCommission();
        }
        
        // Для товаров администратора применяем только скидку
        if ($this->hasActiveDiscount()) {
            $discount = ($this->price * $this->discount_percent) / 100;
            return round($this->price - $discount, 2);
        }
        return $this->price;
    }

    /**
     * Получить цену с учетом комиссии поставщика
     * Формула: final_price = supplier_price / (1 - commission/100)
     * 
     * Пример: поставщик указал 10 USD, комиссия 10%
     * - Поставщик получает: 10 * 0.9 = 9 USD
     * - Покупатель платит: 10 / 0.9 = 11.11 USD
     */
    public function getPriceWithCommission()
    {
        // Если товар администратора, возвращаем цену без комиссии
        if (!$this->supplier_id) {
            if ($this->hasActiveDiscount()) {
                $discount = ($this->price * $this->discount_percent) / 100;
                return round($this->price - $discount, 2);
            }
            return $this->price;
        }

        // Получаем поставщика и его комиссию
        $supplier = $this->supplier;
        if (!$supplier || !$supplier->is_supplier) {
            // Если поставщик не найден или не является поставщиком, возвращаем базовую цену
            if ($this->hasActiveDiscount()) {
                $discount = ($this->price * $this->discount_percent) / 100;
                return round($this->price - $discount, 2);
            }
            return $this->price;
        }

        // Комиссия платформы в процентах (если null — по умолчанию 0)
        $supplierCommission = $supplier->supplier_commission !== null
            ? (float)$supplier->supplier_commission
            : 0.0;

        // Если комиссия 0, возвращаем цену без изменений (с учетом скидки)
        if ($supplierCommission <= 0) {
            if ($this->hasActiveDiscount()) {
                $discount = ($this->price * $this->discount_percent) / 100;
                return round($this->price - $discount, 2);
            }
            return $this->price;
        }

        // Применяем комиссию: final_price = supplier_price / (1 - commission/100)
        $commissionMultiplier = 1 - ($supplierCommission / 100);
        
        // Защита от деления на ноль
        if ($commissionMultiplier <= 0) {
            \Log::warning('ServiceAccount::getPriceWithCommission: Invalid commission multiplier', [
                'product_id' => $this->id,
                'supplier_id' => $supplier->id,
                'supplier_commission' => $supplierCommission,
                'commission_multiplier' => $commissionMultiplier,
            ]);
            return $this->price; // Возвращаем базовую цену в случае ошибки
        }

        $priceWithCommission = $this->price / $commissionMultiplier;

        // Применяем скидку, если активна
        if ($this->hasActiveDiscount()) {
            $discount = ($priceWithCommission * $this->discount_percent) / 100;
            $priceWithCommission = $priceWithCommission - $discount;
        }

        return round($priceWithCommission, 2);
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
        
        // ВАЖНО: Проверяем, что accounts_data является массивом
        if (!is_array($accountsData)) {
            // Если это строка (JSON), пытаемся декодировать
            if (is_string($accountsData) && !empty($accountsData)) {
                $decoded = json_decode($accountsData, true);
                $accountsData = is_array($decoded) ? $decoded : [];
            } else {
                $accountsData = [];
            }
        }
        
        $totalQty = count($accountsData);
        $used = (int)($this->used ?? 0);
        
        // Гарантируем, что результат не отрицательный
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
     * Scope: товары, ожидающие модерации
     */
    public function scopePendingModeration($query)
    {
        return $query->where('moderation_status', 'pending');
    }

    /**
     * Scope: одобренные товары
     */
    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    /**
     * Scope: отклоненные товары
     */
    public function scopeRejected($query)
    {
        return $query->where('moderation_status', 'rejected');
    }

    /**
     * Проверить, требует ли товар модерации
     */
    public function requiresModeration()
    {
        return $this->supplier_id !== null;
    }

    /**
     * Проверить, одобрен ли товар
     */
    public function isApproved()
    {
        // Товары администратора не требуют модерации
        if (!$this->requiresModeration()) {
            return true;
        }
        return $this->moderation_status === 'approved';
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

    /**
     * Получить похожие товары
     * 
     * @param int $limit Количество товаров для возврата
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSimilarProducts(int $limit = 6)
    {
        return Cache::remember(
            "similar_products_{$this->id}_{$limit}",
            3600, // Кеш на 1 час
            function() use ($limit) {
                $query = ServiceAccount::with('category')
                    ->where('is_active', true)
                    ->where('id', '!=', $this->id) // Исключаем текущий товар
                    ->whereNotNull('title')
                    ->whereNotNull('price');

                $scores = collect();

                // 1. Фильтр по категории (40% веса)
                if ($this->category_id) {
                    $categoryProducts = (clone $query)
                        ->where('category_id', $this->category_id)
                        ->get();
                    
                    foreach ($categoryProducts as $product) {
                        $score = $scores->get($product->id, 0);
                        $scores->put($product->id, $score + 40);
                    }
                }

                // 2. Фильтр по цене (30% веса)
                if ($this->price > 0) {
                    $priceRange = $this->price * 0.3; // ±30%
                    $minPrice = max(0, $this->price - $priceRange);
                    $maxPrice = $this->price + $priceRange;
                    
                    $priceProducts = (clone $query)
                        ->whereBetween('price', [$minPrice, $maxPrice])
                        ->get();
                    
                    foreach ($priceProducts as $product) {
                        $score = $scores->get($product->id, 0);
                        // Чем ближе цена, тем больше баллов
                        $priceDiff = abs($product->price - $this->price) / $this->price;
                        $priceScore = 30 * (1 - min($priceDiff, 1));
                        $scores->put($product->id, $score + $priceScore);
                    }
                }

                // 3. Анализ ключевых слов (20% веса)
                $currentTitleWords = $this->extractKeywords($this->title);
                $currentDescWords = $this->extractKeywords($this->description ?? '');
                
                if (!empty($currentTitleWords) || !empty($currentDescWords)) {
                    $allProducts = (clone $query)->get();
                    foreach ($allProducts as $product) {
                        $productTitleWords = $this->extractKeywords($product->title);
                        $productDescWords = $this->extractKeywords($product->description ?? '');
                        
                        $titleMatch = count(array_intersect($currentTitleWords, $productTitleWords));
                        $descMatch = count(array_intersect($currentDescWords, $productDescWords));
                        
                        $keywordScore = min(20, ($titleMatch * 2 + $descMatch) * 2);
                        $score = $scores->get($product->id, 0);
                        $scores->put($product->id, $score + $keywordScore);
                    }
                }

                // 4. Поставщик (10% веса)
                if ($this->supplier_id) {
                    $supplierProducts = (clone $query)
                        ->where('supplier_id', $this->supplier_id)
                        ->get();
                    
                    foreach ($supplierProducts as $product) {
                        $score = $scores->get($product->id, 0);
                        $scores->put($product->id, $score + 10);
                    }
                }

                // Сортируем по баллам и берем топ
                $productIds = $scores->sortDesc()->take($limit)->keys()->toArray();
                
                if (empty($productIds)) {
                    // Fallback: просто товары из той же категории или любые активные
                    if ($this->category_id) {
                        return (clone $query)
                            ->where('category_id', $this->category_id)
                            ->limit($limit)
                            ->get();
                    }
                    
                    // Если нет категории, возвращаем любые активные товары
                    return (clone $query)
                        ->limit($limit)
                        ->orderBy('created_at', 'desc')
                        ->get();
                }

                return ServiceAccount::with('category')
                    ->whereIn('id', $productIds)
                    ->get()
                    ->sortBy(function($product) use ($scores) {
                        return -$scores->get($product->id, 0);
                    })
                    ->take($limit)
                    ->values();
            }
        );
    }

    /**
     * Извлечь ключевые слова из текста
     * 
     * @param string $text Текст для анализа
     * @return array Массив ключевых слов
     */
    private function extractKeywords(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        // Убираем HTML теги, приводим к нижнему регистру
        $text = mb_strtolower(strip_tags($text));
        
        // Убираем знаки препинания
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Разбиваем на слова
        $words = preg_split('/\s+/', $text);
        
        // Фильтруем стоп-слова и короткие слова
        $stopWords = ['и', 'в', 'на', 'с', 'для', 'от', 'до', 'по', 'из', 'к', 'а', 'но', 'или', 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'та', 'що', 'як', 'для', 'від', 'до', 'по', 'з', 'і', 'або'];
        $words = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_unique(array_values($words));
    }
}
