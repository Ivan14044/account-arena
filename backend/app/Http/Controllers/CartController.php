<?php

namespace App\Http\Controllers;

use App\Models\{Service, Subscription, Transaction, Option, Promocode, PromocodeUsage, ServiceAccount, Purchase};
use App\Services\PromocodeValidationService;
use App\Services\NotificationTemplateService;
use App\Services\EmailService;
use App\Services\NotifierService;
use App\Services\ProductPurchaseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(\App\Http\Requests\Cart\CartStoreRequest $request, PromocodeValidationService $promoService, ProductPurchaseService $purchaseService)
    {
        // Валидация вынесена в FormRequest (CartStoreRequest)
        
        // Хотя бы один из массивов должен быть заполнен
        if (empty($request->services) && empty($request->products)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        $user = $this->getApiUser($request);

        // Проверяем, если payment_method = admin_bypass, то пользователь должен быть админом
        if ($request->payment_method === 'admin_bypass' && !$user->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Извлекаем ID сервисов и информацию о типах подписок
        $services = collect();
        $subscriptionTypes = [];
        $hasTrialServices = false;
        $nextPaymentDate = Carbon::now()->addMonth();
        
        if (!empty($request->services)) {
            $serviceIds = collect($request->services)->pluck('id')->toArray();
            $subscriptionTypes = collect($request->services)->pluck('subscription_type', 'id')->toArray();
            $hasTrialServices = collect($request->services)->contains('subscription_type', 'trial');
            $nextPaymentDate = $hasTrialServices
                ? Carbon::now()->addDays(Option::get('trial_days'))
                : Carbon::now()->addMonth();
            $services = Service::find($serviceIds);
        }

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
            // Рассчитываем общую стоимость для сервисов
            $servicesTotal = 0;
            if (!empty($request->services)) {
                foreach ($services as $service) {
                    $subscriptionType = $subscriptionTypes[$service->id] ?? 'trial';
                    $amount = $subscriptionType === 'trial' ? ($service->trial_amount ?? 0) : ($service->amount ?? 0);
                    $servicesTotal += $amount;
                }
            }

            // Рассчитываем общую стоимость для товаров используя сервис
            $productsTotal = 0;
            $productsData = [];
            if (!empty($request->products)) {
                $prepareResult = $purchaseService->prepareProductsData($request->products);
                if (!$prepareResult['success']) {
                    return response()->json([
                        'success' => false, 
                        'message' => $prepareResult['message']
                    ], 422);
                }
                $productsData = $prepareResult['data'];
                $productsTotal = $prepareResult['total'];
            }

            $totalAmount = $servicesTotal + $productsTotal;

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

            // Создаем подписки, покупки товаров и транзакции
            DB::transaction(function () use ($services, $user, $subscriptionTypes, $nextPaymentDate, $hasTrialServices, $productsData, $totalAmount) {
                // Обработка сервисов (подписки)
                if (!empty($services)) {
                    foreach ($services as $service) {
                        $subscriptionType = $subscriptionTypes[$service->id] ?? 'trial';
                        $amount = $subscriptionType === 'trial' ? ($service->trial_amount ?? 0) : ($service->amount ?? 0);

                        // Проверяем существующую подписку
                        $existing = Subscription::where('user_id', $user->id)
                            ->where('service_id', $service->id)
                            ->orderByDesc('id')
                            ->first();

                        if ($existing) {
                            $existing->status = Subscription::STATUS_ACTIVE;
                            $existing->payment_method = 'balance';
                            $existing->is_auto_renew = 0;
                            $existing->is_trial = $subscriptionType === 'trial';
                            $existing->next_payment_at = $nextPaymentDate;
                            $existing->save();
                            $subId = $existing->id;
                        } else {
                            $subscription = Subscription::create([
                                'user_id' => $user->id,
                                'status' => Subscription::STATUS_ACTIVE,
                                'payment_method' => 'balance',
                                'service_id' => $service->id,
                                'is_auto_renew' => 0,
                                'is_trial' => $subscriptionType === 'trial',
                                'next_payment_at' => $nextPaymentDate,
                            ]);
                            $subId = $subscription->id;
                        }

                        // Создаем транзакцию для подписки
                        Transaction::create([
                            'user_id' => $user->id,
                            'amount' => $amount,
                            'currency' => Option::get('currency'),
                            'payment_method' => 'balance',
                            'subscription_id' => $subId,
                            'status' => 'completed',
                        ]);
                    }
                }

                // Обработка товаров используя сервис (устранение дублирования кода)
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
                'services' => $services ? $services->pluck('id')->toArray() : [],
                'products_count' => count($productsData),
            ]);

            // Отправляем уведомления пользователю о покупке товаров
            if (!empty($productsData)) {
                foreach ($productsData as $item) {
                    $product = $item['product'];
                    
                    // Внутреннее уведомление в систему
                    app(NotificationTemplateService::class)->sendToUser($user, 'product_purchase', [
                        'product_name' => $product->title,
                        'quantity' => $item['quantity'],
                        'total' => number_format($item['total'], 2),
                        'order_number' => Purchase::where('service_account_id', $product->id)
                            ->where('user_id', $user->id)
                            ->latest()
                            ->first()
                            ->order_number ?? 'N/A',
                    ]);
                }
                
                // Email подтверждение покупки
                EmailService::send('product_purchase_confirmation', $user->id, [
                    'products_count' => count($productsData),
                    'total_amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency')),
                ]);
            }

            // Отправляем общее уведомление об оплате с баланса
            EmailService::send('payment_confirmation', $user->id, [
                'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
            ]);

            // Уведомление админу о новом заказе
            if (!empty($productsData)) {
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
            }

            return \App\Http\Responses\ApiResponse::success(['message' => 'Payment completed successfully']);
        }

        // payment_method: free — only allowed with free_access promo; force premium, 0.00 amounts; merge services from promo
        if ($request->payment_method === 'free') {
            if (!$promoData || ($promoData['type'] ?? '') !== 'free_access') {
                return response()->json(['success' => false, 'message' => 'Promocode is discount, not free access'], 422);
            }

            // Build final set of services: payload + services from promo
            $promoServiceIds = collect($promoData['services'] ?? [])->pluck('id')->all();
            $finalServiceIds = collect($serviceIds)->merge($promoServiceIds)->unique()->values()->all();
            $services = Service::find($finalServiceIds);

            $freeMap = collect($promoData['services'] ?? [])->keyBy('id');

            DB::transaction(function () use ($services, $user, $freeMap, $promoData) {
                foreach ($services as $service) {
                    $days = (int)($freeMap->get($service->id)['free_days'] ?? 0);

                    // Find existing subscription for this user/service
                    $existing = Subscription::where('user_id', $user->id)
                        ->where('service_id', $service->id)
                        ->orderByDesc('id')
                        ->first();

                    $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                        ? Carbon::parse($existing->next_payment_at)
                        : Carbon::now();

                    $nextAt = (clone $baseDate)->addDays(max(0, $days));

                    if ($existing) {
                        $existing->status = Subscription::STATUS_ACTIVE;
                        $existing->payment_method = 'free';
                        $existing->is_auto_renew = 0;
                        $existing->is_trial = 0;
                        $existing->next_payment_at = $nextAt;
                        $existing->save();
                        $subId = $existing->id;
                    } else {
                        $subscription = Subscription::create([
                            'user_id' => $user->id,
                            'status' => Subscription::STATUS_ACTIVE,
                            'payment_method' => 'free',
                            'service_id' => $service->id,
                            'is_auto_renew' => 0,
                            'is_trial' => 0,
                            'next_payment_at' => $nextAt,
                        ]);
                        $subId = $subscription->id;
                    }

                    Transaction::create([
                        'user_id' => $user->id,
                        'amount' => 0.00,
                        'currency' => Option::get('currency'),
                        'payment_method' => 'free',
                        'subscription_id' => $subId,
                    ]);
                }

                // Record usage
                $promo = Promocode::where('code', $promoData['code'] ?? '')->lockForUpdate()->first();
                if ($promo) {
                    PromocodeUsage::create([
                        'promocode_id' => $promo->id,
                        'user_id' => $user->id,
                        'order_id' => 'free_' . $user->id . '_' . time(),
                    ]);
                    if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                        $promo->usage_count = (int)$promo->usage_count + 1;
                        $promo->save();
                    }
                }
            });

            return \App\Http\Responses\ApiResponse::success();
        }

        foreach ($services as $service) {
            $existing = Subscription::where('user_id', $user->id)
                ->where('service_id', $service->id)
                ->orderByDesc('id')
                ->first();

            $map = collect($promoData['services'] ?? [])->keyBy('id');
            $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                ? Carbon::parse($existing->next_payment_at)
                : $nextPaymentDate;

            $nextAt = ($promoData && ($promoData['type'] ?? '') === 'free_access' && $map->has($service->id))
                ? (clone $baseDate)->addDays(max(0, (int)$map->get($service->id)['free_days'] ?? 0))
                : $baseDate;

            if ($existing) {
                $existing->status = Subscription::STATUS_ACTIVE;
                $existing->payment_method = $request->payment_method;
                $existing->is_auto_renew = 0;
                $existing->is_trial = $hasTrialServices;
                $existing->next_payment_at = $nextAt;
                $existing->save();
                $subId = $existing->id;
            } else {
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'payment_method' => $request->payment_method,
                    'service_id' => $service->id,
                    'is_auto_renew' => 0,
                    'is_trial' => $hasTrialServices,
                    'next_payment_at' => $nextAt,
                ]);
                $subId = $subscription->id;
            }

            Transaction::create([
                'user_id' => $user->id,
                'amount' => ($promoData && ($promoData['type'] ?? '') === 'free_access' && $map->has($service->id)) ? 0.00 : $service->amount,
                'currency' => Option::get('currency'),
                'payment_method' => $request->payment_method,
                'subscription_id' => $subId,
            ]);
        }

        // Record usage if promo applied
        if ($promoData) {
            DB::transaction(function () use ($promoData, $user) {
                $promo = Promocode::where('code', $promoData['code'] ?? '')->lockForUpdate()->first();
                if ($promo) {
                    PromocodeUsage::create([
                        'promocode_id' => $promo->id,
                        'user_id' => $user->id,
                        'order_id' => 'free_' . $user->id . '_' . time(),
                    ]);

                    if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                        $promo->usage_count = (int)$promo->usage_count + 1;
                        $promo->save();
                    }
                }
            });
        }

        return \App\Http\Responses\ApiResponse::success();
    }

    public function cancelSubscription(\App\Http\Requests\Cart\CancelSubscriptionRequest $request)
    {
        // Валидация вынесена в FormRequest (CancelSubscriptionRequest)

        // ИСПРАВЛЕНО: Добавлена проверка принадлежности подписки пользователю
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $subscription->update(['status' => Subscription::STATUS_CANCELED]);

        return \App\Http\Responses\ApiResponse::success();
    }
}
