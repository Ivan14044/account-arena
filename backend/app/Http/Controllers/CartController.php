<?php

namespace App\Http\Controllers;

use App\Models\{Service, Subscription, Transaction, Option, Promocode, PromocodeUsage, ServiceAccount, Purchase};
use App\Services\PromocodeValidationService;
use App\Services\NotificationTemplateService;
use App\Services\EmailService;
use App\Services\NotifierService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request, PromocodeValidationService $promoService)
    {
        $request->validate([
            'services' => 'nullable|array',
            'services.*.id' => 'required|integer|exists:services,id',
            'services.*.subscription_type' => 'required|in:trial,premium',
            'products' => 'nullable|array',
            'products.*.id' => 'required|integer|exists:service_accounts,id',
            'products.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:credit_card,crypto,admin_bypass,free,balance',
            'promocode' => 'nullable|string|required_if:payment_method,free',
        ]);
        
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

            // Рассчитываем общую стоимость для товаров
            $productsTotal = 0;
            $productsData = [];
            if (!empty($request->products)) {
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

                // Обработка товаров
                if (!empty($productsData)) {
                    foreach ($productsData as $item) {
                        $product = $item['product'];
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
                        
                        // Создаем транзакцию для покупки
                        $transaction = Transaction::create([
                            'user_id' => $user->id,
                            'amount' => $total,
                            'currency' => Option::get('currency'),
                            'payment_method' => 'balance',
                            'service_account_id' => $product->id, // ИСПРАВЛЕНО: Добавлено для поддержки претензий
                            'status' => 'completed',
                        ]);
                        
                        // Создаем запись о покупке с уникальным номером заказа
                        $purchase = Purchase::create([
                            'order_number' => Purchase::generateOrderNumber(),
                            'user_id' => $user->id,
                            'service_account_id' => $product->id,
                            'transaction_id' => $transaction->id,
                            'quantity' => $quantity,
                            'price' => $price,
                            'total_amount' => $total,
                            'account_data' => $assignedAccounts,
                            'status' => 'completed',
                        ]);
                        
                        // Логируем номер заказа для отслеживания
                        \Log::info('Purchase created with order number', [
                            'order_number' => $purchase->order_number,
                            'user_id' => $user->id,
                            'product_id' => $product->id,
                            'product_title' => $product->title,
                        ]);
                    }
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

            return response()->json(['success' => true, 'message' => 'Payment completed successfully']);
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

            return response()->json(['success' => true]);
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

        return response()->json(['success' => true]);
    }

    public function cancelSubscription(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|integer',
        ]);

        // ИСПРАВЛЕНО: Добавлена проверка принадлежности подписки пользователю
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $subscription->update(['status' => Subscription::STATUS_CANCELED]);

        return response()->json(['success' => true]);
    }
}
