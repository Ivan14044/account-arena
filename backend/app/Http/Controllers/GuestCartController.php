<?php

namespace App\Http\Controllers;

use App\Models\{ServiceAccount, Purchase, Transaction, Option, Promocode};
use App\Services\PromocodeValidationService;
use App\Services\ProductPurchaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Контроллер для обработки гостевых покупок (покупки без авторизации)
 * Гости могут покупать только ТОВАРЫ (не подписки)
 * Гости должны указать email для получения информации о покупке
 */
class GuestCartController extends Controller
{
    /**
     * Создание гостевого заказа (только для товаров)
     * Поддерживаемые методы оплаты: card (Mono), crypto (Cryptomus)
     */
    public function store(Request $request, PromocodeValidationService $promoService, ProductPurchaseService $purchaseService)
    {
        $request->validate([
            'guest_email' => 'required|email', // Email обязателен для гостей
            'products' => 'required|array|min:1', // Только товары
            'products.*.id' => 'required|integer|exists:service_accounts,id',
            'products.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:card,crypto', // Только публичные методы оплаты
            'promocode' => 'nullable|string',
        ]);

        $guestEmail = strtolower(trim($request->guest_email));

        // Validate promocode if provided
        $promoData = null;
        $promocodeParam = trim((string)$request->promocode);
        if ($promocodeParam !== '') {
            // Для гостей передаем null как user_id
            $promoData = $promoService->validate($promocodeParam, null);
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? 'Invalid promocode'], 422);
            }
        }

        // Рассчитываем общую стоимость товаров используя сервис (устранение дублирования кода)
        $prepareResult = $purchaseService->prepareProductsData($request->products);
        if (!$prepareResult['success']) {
            return response()->json([
                'success' => false, 
                'message' => $prepareResult['message']
            ], 422);
        }
        
        $productsData = $prepareResult['data'];
        $productsTotal = $prepareResult['total'];

        $totalAmount = $productsTotal;

        // Применяем скидку по промокоду если есть
        if ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $discountPercent = floatval($promoData['discount_percent'] ?? 0);
            $totalAmount = $totalAmount - ($totalAmount * $discountPercent / 100);
        }

        // Сохраняем данные заказа в сессии для последующей обработки после оплаты
        $orderData = [
            'guest_email' => $guestEmail,
            'products' => $request->products,
            'products_data' => collect($productsData)->map(function($item) {
                return [
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ];
            })->toArray(),
            'total_amount' => $totalAmount,
            'promocode' => $promocodeParam,
            'promo_data' => $promoData,
        ];

        // Возвращаем данные для инициации платежа
        return response()->json([
            'success' => true,
            'order_data' => $orderData,
            'total_amount' => $totalAmount,
            'currency' => Option::get('currency', 'USD'),
        ]);
    }

    /**
     * Создание записей о покупке после успешной оплаты
     * Вызывается из webhook'ов платежных систем
     * Использует ProductPurchaseService для устранения дублирования кода
     */
    public static function createGuestPurchases(string $guestEmail, array $productsData, ?string $promocode = null)
    {
        $purchaseService = app(ProductPurchaseService::class);
        
        // Используем массовое создание покупок из сервиса
        // Это обеспечит атомарность, уведомления и инвалидацию кеша
        return $purchaseService->createMultiplePurchases(
            $productsData, 
            null, // userId = null для гостей
            $guestEmail, 
            'guest_purchase'
        );
    }
}




