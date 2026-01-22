<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Cache;

class AccountController extends Controller
{
    /**
     * Получить список активных товаров с кешированием (кеш на 5 минут)
     * Кеш автоматически очищается при изменении товаров
     */
    public function index()
    {
        $data = Cache::remember('active_accounts_list_v4', 300, function () {
            $accounts = ServiceAccount::with(['category', 'supplier'])
                ->select([
                    'id', 'sku', 'title', 'title_en', 'title_uk', 
                    'description', 'description_en', 'description_uk',
                    'price', 'discount_percent', 'discount_start_date', 'discount_end_date',
                    'image_url', 'category_id', 'supplier_id',
                    'used', 'delivery_type', 'created_at', 'is_active', 'moderation_status', 'sort_order', 'slug'
                ])
                ->selectRaw('JSON_LENGTH(accounts_data) as total_qty_from_json')
                ->where('is_active', true)
                ->whereNotNull('title')
                ->whereNotNull('price')
                // ВАЖНО: Показываем только одобренные товары или товары администратора
                ->where(function($query) {
                    $query->where('moderation_status', 'approved')
                          ->orWhereNull('supplier_id'); // Товары администратора не требуют модерации
                })
                ->orderBy('sort_order', 'asc') // Использовать sort_order для ручной сортировки
                ->orderBy('id', 'desc') // Дополнительная сортировка по id
                ->get();

            return $accounts->map(function ($account) {
                // Используем значение из JSON_LENGTH если оно есть
                if ($account->requiresManualDelivery()) {
                    $availableCount = $account->is_active ? 999 : 0;
                    $totalQuantity = 0;
                    $soldCount = 0;
                } else {
                    $totalQuantity = (int)($account->total_qty_from_json ?? 0);
                    $soldCount = (int)($account->used ?? 0);
                    $availableCount = max(0, $totalQuantity - $soldCount);
                }
                
                return [
                    'id' => $account->id,
                    'slug' => $account->slug,
                    'sku' => $account->sku,
                    'title' => $account->title,
                    'title_en' => $account->title_en,
                    'title_uk' => $account->title_uk,
                    'description' => $account->description,
                    'description_en' => $account->description_en,
                    'description_uk' => $account->description_uk,
                    'price' => $account->price,
                    'discount_percent' => $account->discount_percent,
                    'current_price' => $account->getPriceWithCommission(),
                    'has_discount' => $account->hasActiveDiscount(),
                    'image_url' => $account->image_url,
                    'category' => $account->category ? [
                        'id' => $account->category->id,
                        'name' => $account->category->admin_name ?? null,
                        'slug' => $account->category->slug
                    ] : null,
                    'quantity' => $availableCount,
                    'total_quantity' => $totalQuantity,
                    'sold' => $soldCount,
                    'delivery_type' => $account->delivery_type ?? 'automatic',
                    'created_at' => $account->created_at->toISOString(),
                ];
            })->values()->toArray();
        });

        return response()->json($data);
    }

    public function show($id)
    {
        // Поиск по ID или артикулу (SKU)
        $account = ServiceAccount::with('category')
            ->where('is_active', true)
            // ВАЖНО: Показываем только одобренные товары или товары администратора
            ->where(function($query) {
                $query->where('moderation_status', 'approved')
                      ->orWhereNull('supplier_id'); // Товары администратора не требуют модерации
            })
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                      ->orWhere('sku', $id)
                      ->orWhere('slug', $id);
            })
            ->firstOrFail();
        
        // Увеличиваем количество просмотров
        $account->increment('views');

        // Используем метод getAvailableStock() из модели, который уже учитывает ручную выдачу
        $availableCount = $account->getAvailableStock();
        
        // Для total_quantity и sold - только для автоматической выдачи
        $deliveryType = $account->delivery_type ?? 'automatic';
        $isManualDelivery = ($deliveryType === 'manual');
        
        if ($isManualDelivery) {
            // Для товаров с ручной выдачей total_quantity и sold не имеют смысла
            $totalQuantity = 0;
            $soldCount = 0;
        } else {
            // Для автоматической выдачи - стандартная логика
            $totalQuantity = is_array($account->accounts_data) ? count($account->accounts_data) : 0;
            $soldCount = $account->used ?? 0;
        }

        $data = [
            'id' => $account->id,
            'sku' => $account->sku, // Артикул товара
            'title' => $account->title,
            'title_en' => $account->title_en,
            'title_uk' => $account->title_uk,
            'description' => $account->description,
            'description_en' => $account->description_en,
            'description_uk' => $account->description_uk,
            'additional_description' => $account->additional_description,
            'additional_description_en' => $account->additional_description_en,
            'additional_description_uk' => $account->additional_description_uk,
            'meta_title' => $account->meta_title,
            'meta_title_en' => $account->meta_title_en,
            'meta_title_uk' => $account->meta_title_uk,
            'meta_description' => $account->meta_description,
            'meta_description_en' => $account->meta_description_en,
            'meta_description_uk' => $account->meta_description_uk,
            'price' => $account->price,
            'discount_percent' => $account->discount_percent,
            'current_price' => $account->getPriceWithCommission(), // ВАЖНО: Используем getPriceWithCommission() для применения комиссии
            'has_discount' => $account->hasActiveDiscount(),
            'image_url' => $account->image_url,
            'category' => $account->category ? [
                'id' => $account->category->id,
                'name' => $account->category->admin_name ?? null,
            ] : null,
            'show_only_telegram' => $account->show_only_telegram,
            'quantity' => $availableCount,
            'total_quantity' => $totalQuantity,
            'sold' => $soldCount,
            'views' => (int)($account->views ?? 0),
            'delivery_type' => $deliveryType,
            'created_at' => $account->created_at->toISOString(),
        ];

        return response()->json($data);
    }

    /**
     * Получить похожие товары
     */
    public function similar($id)
    {
        $account = ServiceAccount::where('is_active', true)
            // ВАЖНО: Показываем только одобренные товары или товары администратора
            ->where(function($query) {
                $query->where('moderation_status', 'approved')
                      ->orWhereNull('supplier_id'); // Товары администратора не требуют модерации
            })
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                      ->orWhere('sku', $id);
            })
            ->firstOrFail();

        // Кэшируем похожие товары на 1 час (уже есть внутри getSimilarProducts, но здесь мы маппим данные)
        $similar = $account->getSimilarProducts(6);

        $data = $similar->map(function ($item) {
            // Используем метод getAvailableStock() из модели
            $availableCount = $item->getAvailableStock();
            
            // Для total_quantity и sold - только для автоматической выдачи
            $deliveryType = $item->delivery_type ?? 'automatic';
            $isManualDelivery = ($deliveryType === 'manual');
            
            if ($isManualDelivery) {
                $totalQuantity = 0;
                $soldCount = 0;
            } else {
                // ВАЖНО: В getSimilarProducts теперь выбирается accounts_data, 
                // так что можем корректно посчитать количество.
                $totalQuantity = isset($item->total_qty_from_json) 
                    ? (int)$item->total_qty_from_json 
                    : (is_array($item->accounts_data) ? count($item->accounts_data) : 0);
                $soldCount = (int)($item->used ?? 0);
            }

            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'title' => $item->title,
                'title_en' => $item->title_en,
                'title_uk' => $item->title_uk,
                'description' => $item->description,
                'description_en' => $item->description_en,
                'description_uk' => $item->description_uk,
                'price' => $item->price,
                'discount_percent' => $item->discount_percent,
                'current_price' => $item->getPriceWithCommission(),
                'has_discount' => $item->hasActiveDiscount(),
                'image_url' => $item->image_url,
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'name' => $item->category->admin_name ?? null,
                ] : null,
                'quantity' => $availableCount,
                'total_quantity' => $totalQuantity,
                'sold' => $soldCount,
                'delivery_type' => $deliveryType,
                'created_at' => $item->created_at->toISOString(),
            ];
        });

        return response()->json($data->values());
    }
}

