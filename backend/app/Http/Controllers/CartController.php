<?php

namespace App\Http\Controllers;

use App\Models\{Transaction, Option, Promocode, PromocodeUsage, ServiceAccount, Purchase};
use App\Services\PromocodeValidationService;
use App\Services\EmailService;
use App\Services\NotifierService;
use App\Services\ProductPurchaseService;
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

            // Применяем скидку по промокоду если есть
            if ($promoData && ($promoData['type'] ?? '') === 'discount') {
                $discountPercent = floatval($promoData['discount_percent'] ?? 0);
                $totalAmount = $totalAmount - ($totalAmount * $discountPercent / 100);
            }

            // ИСПРАВЛЕНО: Правильная проверка баланса с учетом null
            $currentBalance = $user->balance ?? 0;
            if ($currentBalance < $totalAmount) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Insufficient balance. Your balance: ' . $currentBalance . ' USD, required: ' . $totalAmount . ' USD'
                ], 422);
            }

            // Списываем средства с баланса
            $user->balance = $currentBalance - $totalAmount;
            $user->save();

            // Создаем покупки товаров и транзакции
            DB::transaction(function () use ($productsData, $user, $totalAmount, $purchaseService) {
                // Обработка товаров используя сервис
                if (!empty($productsData)) {
                    $purchaseService->createMultiplePurchases($productsData, $user->id, null, 'balance');
                }

                // Создаем транзакцию списания с баланса
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => -$totalAmount, // Отрицательная сумма = списание
                    'currency' => Option::get('currency'),
                    'payment_method' => 'balance_deduction',
                    'status' => 'completed',
                ]);
            });

            // Записываем использование промокода если применялся
            if ($promoData) {
                DB::transaction(function () use ($promoData, $user) {
                    $promo = Promocode::where('code', $promoData['code'] ?? '')->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                            'promocode_id' => $promo->id,
                            'user_id' => $user->id,
                            'order_id' => 'balance_' . $user->id . '_' . time(),
                        ]);

                        if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                            $promo->usage_count = (int)$promo->usage_count + 1;
                            $promo->save();
                        }
                    }
                });
            }

            \Log::info('Balance payment completed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'total_amount' => $totalAmount,
                'old_balance' => $user->balance + $totalAmount,
                'new_balance' => $user->balance,
                'products_count' => count($productsData),
            ]);

            // Email подтверждение покупки
            EmailService::send('product_purchase_confirmation', $user->id, [
                'products_count' => count($productsData),
                'total_amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency')),
            ]);

            // Отправляем общее уведомление об оплате с баланса
            EmailService::send('payment_confirmation', $user->id, [
                'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
            ]);

            // Уведомление админу о новом заказе
            NotifierService::send(
                'product_purchase',
                __('notifier.new_product_purchase_title', ['method' => 'Balance']),
                __('notifier.new_product_purchase_message', [
                    'email' => $user->email,
                    'name' => $user->name,
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ])
            );

            return \App\Http\Responses\ApiResponse::success(['message' => 'Payment completed successfully']);
        }

        // Для других методов оплаты возвращаем ошибку
        return response()->json([
            'success' => false,
            'message' => 'Only balance payment method is supported for products'
        ], 422);
    }

}
