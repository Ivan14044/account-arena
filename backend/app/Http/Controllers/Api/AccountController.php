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
        $accounts = Cache::remember('active_accounts_list', 300, function () {
            return ServiceAccount::with('category')
                ->where('is_active', true)
                ->whereNotNull('title')
                ->whereNotNull('price')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        $data = $accounts->map(function ($account) {
            // Handle accounts_data - normalize NULL to empty array
            $accountsData = $account->accounts_data;
            if (!is_array($accountsData)) {
                // If NULL or not an array, treat as empty
                $accountsData = [];
            }
            
            $totalQuantity = count($accountsData);
            $soldCount = $account->used ?? 0;
            $availableCount = max(0, $totalQuantity - $soldCount);
            
            return [
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
                'current_price' => $account->getCurrentPrice(),
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
                'created_at' => $account->created_at->toISOString(),
            ];
        });

        // Filter out items with 0 quantity (no available items to sell)
        $data = $data->filter(function ($item) {
            return $item['quantity'] > 0;
        });

        return response()->json($data->values());
    }

    public function show($id)
    {
        // Поиск по ID или артикулу (SKU)
        $account = ServiceAccount::with('category')
            ->where('is_active', true)
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                      ->orWhere('sku', $id);
            })
            ->firstOrFail();

        $totalQuantity = is_array($account->accounts_data) ? count($account->accounts_data) : 0;
        $soldCount = $account->used ?? 0;
        $availableCount = max(0, $totalQuantity - $soldCount);

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
            'current_price' => $account->getCurrentPrice(),
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
            'created_at' => $account->created_at->toISOString(),
        ];

        return response()->json($data);
    }
}

