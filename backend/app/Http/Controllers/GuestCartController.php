<?php

namespace App\Http\Controllers;

use App\Models\{ServiceAccount, Purchase, Transaction, Option, Promocode};
use App\Services\PromocodeValidationService;
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
    public function store(Request $request, PromocodeValidationService $promoService)
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

        // Рассчитываем общую стоимость товаров
        $productsTotal = 0;
        $productsData = [];
        
        foreach ($request->products as $productItem) {
            $product = ServiceAccount::find($productItem['id']);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }
            
            $quantity = $productItem['quantity'];
            $available = $product->getAvailableStock();
            
            // Проверяем доступность товара
            if ($available < $quantity) {
                return response()->json([
                    'success' => false, 
                    'message' => "Insufficient stock for {$product->title}. Available: {$available}, requested: {$quantity}"
                ], 422);
            }
            
            $price = $product->getCurrentPrice();
            $itemTotal = $price * $quantity;
            $productsTotal += $itemTotal;
            
            $productsData[] = [
                'product' => $product,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $itemTotal,
            ];
        }

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
     */
    public static function createGuestPurchases(string $guestEmail, array $productsData, ?string $promocode = null)
    {
        DB::transaction(function () use ($guestEmail, $productsData, $promocode) {
            foreach ($productsData as $item) {
                $product = ServiceAccount::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                $quantity = $item['quantity'];
                $price = $item['price'];
                $total = $item['total'];
                
                // Получаем аккаунты из accounts_data
                $accountsData = $product->accounts_data ?? [];
                $usedCount = $product->used ?? 0;
                
                // Выбираем нужное количество неиспользованных аккаунтов
                $assignedAccounts = [];
                for ($i = 0; $i < $quantity; $i++) {
                    if (isset($accountsData[$usedCount + $i])) {
                        $assignedAccounts[] = $accountsData[$usedCount + $i];
                    }
                }
                
                // Увеличиваем счетчик использованных
                $product->used = $usedCount + $quantity;
                $product->save();
                
                // Создаем транзакцию для гостевой покупки
                $transaction = Transaction::create([
                    'user_id' => null, // Гостевая покупка
                    'guest_email' => $guestEmail,
                    'amount' => $total,
                    'currency' => Option::get('currency'),
                    'payment_method' => 'guest_purchase',
                    'service_account_id' => $product->id,
                    'status' => 'completed',
                ]);
                
                // Создаем запись о покупке с уникальным номером заказа
                $purchase = Purchase::create([
                    'order_number' => Purchase::generateOrderNumber(),
                    'user_id' => null, // Гостевая покупка
                    'guest_email' => $guestEmail,
                    'service_account_id' => $product->id,
                    'transaction_id' => $transaction->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total_amount' => $total,
                    'account_data' => $assignedAccounts,
                    'status' => 'completed',
                ]);
                
                // Логируем номер заказа для отслеживания
                \Log::info('Guest purchase created', [
                    'order_number' => $purchase->order_number,
                    'guest_email' => $guestEmail,
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                ]);
            }
        });
    }
}

