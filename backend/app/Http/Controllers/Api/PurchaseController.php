<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Option;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Получить список покупок текущего пользователя
     * Поддерживает фильтрацию по дате и статусу
     */
    public function index(\App\Http\Requests\Purchase\PurchaseIndexRequest $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Получаем locale из заголовка X-Locale
        $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }
        
        // Начинаем запрос с основными условиями
        // Eager loading для избежания N+1 запросов
        $query = Purchase::with([
            'serviceAccount' => function($q) {
                $q->select('id', 'title', 'title_en', 'title_uk', 'image_url');
            },
            'transaction' => function($q) {
                $q->select('id', 'currency', 'payment_method');
            },
            'transaction.dispute'
        ])->where('user_id', $user->id);
        
        // Фильтрация по дате "с"
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        // Фильтрация по дате "по"
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Фильтрация по статусу
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $purchases = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($purchase) use ($locale) {
                // Получаем локализованное название товара
                $localizedTitle = $this->getLocalizedTitle($purchase->serviceAccount, $locale);
                
                return [
                    'id' => $purchase->id,
                    'order_number' => $purchase->order_number,
                    'transaction_id' => $purchase->transaction_id, // Для создания претензий
                    'product' => [
                        'id' => $purchase->serviceAccount->id,
                        'title' => $localizedTitle,
                        'image_url' => $purchase->serviceAccount->image_url,
                    ],
                    'quantity' => $purchase->quantity,
                    'price' => $purchase->price,
                    'total_amount' => $purchase->total_amount,
                    'account_data' => $purchase->account_data, // Данные купленных аккаунтов
                    'status' => $purchase->status,
                    'purchased_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    
                    // Дополнительные поля для совместимости с ProfilePage
                    'service_name' => $localizedTitle,
                    'amount' => $purchase->total_amount,
                    'currency' => $purchase->transaction ? $purchase->transaction->currency : Option::get('currency', 'USD'),
                    'payment_method' => $purchase->transaction ? $purchase->transaction->payment_method : 'unknown',
                    'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    
                    // Информация о претензии (если существует)
                    'has_dispute' => $purchase->transaction && $purchase->transaction->dispute ? true : false,
                    'dispute' => $purchase->transaction && $purchase->transaction->dispute ? [
                        'id' => $purchase->transaction->dispute->id,
                        'status' => $purchase->transaction->dispute->status,
                        'admin_decision' => $purchase->transaction->dispute->admin_decision,
                    ] : null,
                ];
            });
        
        return \App\Http\Responses\ApiResponse::success([
            'purchases' => $purchases,
        ]);
    }
    
    /**
     * Получить конкретную покупку
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Получаем locale из заголовка X-Locale
        $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }
        
        $purchase = Purchase::with(['serviceAccount', 'transaction'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }
        
        // Получаем локализованное название товара
        $localizedTitle = $this->getLocalizedTitle($purchase->serviceAccount, $locale);
        
        return \App\Http\Responses\ApiResponse::success([
            'purchase' => [
                'id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'product' => [
                    'id' => $purchase->serviceAccount->id,
                    'title' => $localizedTitle,
                    'description' => $purchase->serviceAccount->description,
                    'image_url' => $purchase->serviceAccount->image_url,
                ],
                'quantity' => $purchase->quantity,
                'price' => $purchase->price,
                'total_amount' => $purchase->total_amount,
                'account_data' => $purchase->account_data,
                'status' => $purchase->status,
                'purchased_at' => $purchase->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
    
    /**
     * Получить локализованное название товара
     */
    private function getLocalizedTitle($serviceAccount, $locale)
    {
        if ($locale === 'en' && !empty($serviceAccount->title_en)) {
            return $serviceAccount->title_en;
        }
        
        if ($locale === 'uk' && !empty($serviceAccount->title_uk)) {
            return $serviceAccount->title_uk;
        }
        
        // Fallback на базовое название (ru)
        return $serviceAccount->title;
    }
    
    /**
     * Скачать купленные товары в виде текстового файла с кодировкой UTF-8
     */
    public function download(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $purchase = Purchase::with(['serviceAccount'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }
        
        // Формируем содержимое файла из купленных аккаунтов
        $accountData = $purchase->account_data;
        if (is_array($accountData) && !empty($accountData)) {
            $content = implode("\n", $accountData);
        } else {
            $content = "Нет данных для скачивания";
        }
        
        // Генерируем имя файла с информацией о покупке
        $filename = 'purchase_' . $purchase->order_number . '_' . date('Y-m-d') . '.txt';
        
        // Возвращаем файл с UTF-8 кодировкой
        return response($content)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
