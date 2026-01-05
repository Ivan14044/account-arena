<?php

namespace App\Http\Controllers;

use App\Models\{Transaction, Option, Promocode, PromocodeUsage, ServiceAccount, Purchase};
use App\Services\PromocodeValidationService;
use App\Services\EmailService;
use App\Services\NotifierService;
use App\Services\ProductPurchaseService;
use App\Services\NotificationTemplateService;
use App\Services\BalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(\App\Http\Requests\Cart\CartStoreRequest $request, PromocodeValidationService $promoService, ProductPurchaseService $purchaseService)
    {
        // Валидация вынесена в FormRequest (CartStoreRequest)
        
        // Проверяем, что корзина не пуста
        if (empty($request->products)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        $user = $this->getApiUser($request);

        // Validate promocode if provided (used for admin_bypass and free)
        $promoData = null;
        $promocodeParam = trim((string)$request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, optional($user)->id);
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? 'Invalid promocode'], 422);
            }
        }

        // payment_method: balance — оплата с баланса пользователя
        if ($request->payment_method === 'balance') {
            // Рассчитываем общую стоимость для товаров используя сервис
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

            // Применяем персональную скидку пользователя (если есть и активна)
            $personalDiscountPercent = $user->getActivePersonalDiscount();
            $promoDiscountPercent = 0;
            
            // Применяем скидку по промокоду если есть
            if ($promoData && ($promoData['type'] ?? '') === 'discount') {
                $promoDiscountPercent = floatval($promoData['discount_percent'] ?? 0);
            }

            // ВАЖНО: Ограничиваем максимальную суммарную скидку 99% (чтобы итоговая сумма была минимум 1%)
            // Это предотвращает ситуацию, когда персональная скидка + промокод дают более 100% скидки
            $totalDiscountPercent = $personalDiscountPercent + $promoDiscountPercent;
            $maxDiscountPercent = min(99, $totalDiscountPercent);
            
            if ($maxDiscountPercent > 0) {
                $totalAmount = $totalAmount - ($totalAmount * $maxDiscountPercent / 100);
            }

            // ВАЖНО: Округляем и проверяем минимальную сумму (как в MonoController и CryptomusController)
            $totalAmount = max(round($totalAmount, 2), 0.01);

            // ИСПРАВЛЕНО: Правильная проверка баланса с учетом null
            $currentBalance = $user->balance ?? 0;
            if ($currentBalance < $totalAmount) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Insufficient balance. Your balance: ' . $currentBalance . ' USD, required: ' . $totalAmount . ' USD'
                ], 422);
            }

            // ВАЖНО: Все операции выполняем в одной транзакции для атомарности
            // Списываем средства с баланса и создаем покупки одновременно
            DB::beginTransaction();
            try {
                // ВАЖНО: Используем BalanceService для списания баланса
                // Это гарантирует создание записей в BalanceTransaction и Transaction
                $balanceService = app(BalanceService::class);
                
                // Списываем средства с баланса через BalanceService
                $balanceTransaction = $balanceService->deduct(
                    $user,
                    $totalAmount,
                    BalanceService::TYPE_PURCHASE,
                    [
                        'products_count' => count($productsData),
                        'payment_method' => 'balance',
                    ]
                );

                // Создаем покупки товаров
                $purchases = [];
                if (!empty($productsData)) {
                    $purchases = $purchaseService->createMultiplePurchases($productsData, $user->id, null, 'balance');
                }

                // ВАЖНО: Проверяем, что покупки были успешно созданы
                if (empty($purchases)) {
                    DB::rollBack();
                    \Log::error('Balance payment: No purchases created after balance deduction', [
                        'user_id' => $user->id,
                        'total_amount' => $totalAmount,
                        'products_count' => count($productsData),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create purchases. Please try again or contact support.'
                    ], 500);
                }

                // Проверяем, что количество созданных покупок соответствует количеству товаров
                if (count($purchases) !== count($productsData)) {
                    DB::rollBack();
                    \Log::error('Balance payment: Mismatch in purchases count', [
                        'user_id' => $user->id,
                        'expected_count' => count($productsData),
                        'created_count' => count($purchases),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Some products could not be purchased. Please try again or contact support.'
                    ], 500);
                }

                // Коммитим транзакцию - товар выдан, баланс списан
                DB::commit();
            } catch (\Throwable $e) {
                // Откатываем транзакцию при ошибке
                DB::rollBack();
                \Log::error('Balance payment failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing payment: ' . $e->getMessage()
                ], 500);
            }

            // Записываем использование промокода если применялся (с проверкой дублирования)
            if ($promoData) {
                DB::transaction(function () use ($promoData, $user, $purchases) {
                    // Генерируем уникальный order_id на основе первой покупки
                    $orderId = !empty($purchases) && isset($purchases[0]) && $purchases[0]->order_number
                        ? 'balance_' . $purchases[0]->order_number
                        : 'balance_' . $user->id . '_' . time();
                    
                    // ВАЖНО: Проверяем, не был ли промокод уже использован для этого заказа
                    $existingUsage = PromocodeUsage::where('order_id', $orderId)->first();
                    if ($existingUsage) {
                        \Log::info('CartController: Promocode already used for this order', [
                            'order_id' => $orderId,
                            'user_id' => $user->id,
                            'promocode' => $promoData['code'] ?? '',
                            'existing_usage_id' => $existingUsage->id,
                        ]);
                        return; // Промокод уже использован
                    }
                    
                    $promo = Promocode::where('code', $promoData['code'] ?? '')->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                            'promocode_id' => $promo->id,
                            'user_id' => $user->id,
                            'order_id' => $orderId,
                        ]);

                        if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                            $promo->usage_count = (int)$promo->usage_count + 1;
                            $promo->save();
                        }
                    }
                });
            }

            // ВАЖНО: Возвращаем ответ пользователю СРАЗУ после создания покупки
            // Уведомления отправляем в фоне, чтобы не блокировать ответ
            \Log::info('Balance payment completed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'total_amount' => $totalAmount,
                'old_balance' => $user->balance + $totalAmount,
                'new_balance' => $user->balance,
                'products_count' => count($productsData),
            ]);

            // Отправляем быстрые уведомления (только запись в БД, не блокирует)
            if (!empty($purchases) && isset($purchases[0]) && $purchases[0]->order_number) {
                try {
                    $notificationService = app(NotificationTemplateService::class);
                    $notificationService->sendToUser($user, 'purchase', [
                        'order_number' => $purchases[0]->order_number,
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Error sending user notification', ['error' => $e->getMessage()]);
                }
            }

            try {
                NotifierService::sendFromTemplate(
                    'product_purchase',
                    'admin_product_purchase',
                    [
                        'method' => 'Balance',
                        'email' => $user->email,
                        'name' => $user->name,
                        'products' => count($productsData),
                        'amount' => number_format($totalAmount, 2),
                    ]
                );
            } catch (\Throwable $e) {
                \Log::error('Error sending admin notification', ['error' => $e->getMessage()]);
            }

            // ВАЖНО: Email отправляем ПОСЛЕ ответа клиенту через register_shutdown_function
            // Это гарантирует, что ответ будет отправлен сразу, а email в фоне
            $emailParams = [
                'user_id' => $user->id,
                'products_count' => count($productsData),
                'total_amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency')),
            ];
            
            register_shutdown_function(function () use ($emailParams) {
                try {
                    EmailService::send('product_purchase_confirmation', $emailParams['user_id'], [
                        'products_count' => $emailParams['products_count'],
                        'total_amount' => $emailParams['total_amount'],
                    ]);
                    EmailService::send('payment_confirmation', $emailParams['user_id'], [
                        'amount' => $emailParams['total_amount']
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Error sending email after balance payment', [
                        'user_id' => $emailParams['user_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return \App\Http\Responses\ApiResponse::success(['message' => 'Payment completed successfully']);
        }

        // Для других методов оплаты возвращаем ошибку
        return response()->json([
            'success' => false,
            'message' => 'Only balance payment method is supported for products'
        ], 422);
    }

}
