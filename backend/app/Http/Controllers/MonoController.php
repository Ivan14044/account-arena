<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Option;
use App\Models\User;
use App\Models\ServiceAccount;
use App\Models\Purchase;
use App\Services\NotificationTemplateService;
use App\Services\MonoPaymentService;
use App\Services\EmailService;
use App\Services\NotifierService;
use App\Services\PromocodeValidationService;
use App\Services\BalanceService;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Http\Controllers\GuestCartController;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MonoController extends Controller
{
    public function createPayment(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        $request->validate([
            'services' => 'required|array|min:1',
            'services.*' => 'integer',
            'subscriptionTypes' => 'nullable',
            'promocode' => 'nullable|string',
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $services = Service::whereIn('id', $request->services)->get();
        if ($services->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Invalid services'], 422);
        }

        $subscriptionTypes = $request->subscriptionTypes ?? [];
        $trialIds = [];
        $totalAmount = 0;

        foreach ($services as $service) {
            $type = $subscriptionTypes[$service->id] ?? null;

            if ($type === 'trial') {
                $trialIds[] = $service->id;
                $totalAmount += $service->trial_amount ?? 0;
            } else {
                $totalAmount += $service->amount;
            }
        }

        $count = $services->count();
        $discount2 = (int) Option::get('discount_2', 0);
        $discount3 = (int) Option::get('discount_3', 0);

        $appliedDiscountPercent = 0;
        if ($count >= 3 && $discount3 > 0) {
            $appliedDiscountPercent = $discount3;
        } elseif ($count >= 2 && $discount2 > 0) {
            $appliedDiscountPercent = $discount2;
        }

        $originalAmount = round($totalAmount, 2);
        $discountAmount = $appliedDiscountPercent > 0
            ? round($originalAmount * $appliedDiscountPercent / 100, 2)
            : 0.00;

        // Apply promocode if provided
        $promoData = null;
        $promocodeParam = trim((string) $request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, $user->id);
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? __('promocodes.invalid')], 422);
            }
        }

        // Per-service price map for applying promo free_access
        $serviceAmounts = [];
        foreach ($services as $service) {
            $type = $subscriptionTypes[$service->id] ?? null;
            $serviceAmounts[$service->id] = $type === 'trial' ? ($service->trial_amount ?? 0) : $service->amount;
        }

        if ($promoData && ($promoData['type'] ?? '') === 'free_access') {
            $freeMap = collect($promoData['services'] ?? [])->keyBy('id');
            foreach ($serviceAmounts as $sid => $amt) {
                if ($freeMap->has($sid)) {
                    $serviceAmounts[$sid] = 0.00; // free covered service
                }
            }
            $originalAmount = round(array_sum($serviceAmounts), 2);
            // Discount2/3 не применяется поверх free — но оставляем их как есть, т.к. суммы уже нули для covered
            $discountAmount = $appliedDiscountPercent > 0
                ? round($originalAmount * $appliedDiscountPercent / 100, 2)
                : 0.00;
        } elseif ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $promoPercent = (int)($promoData['discount_percent'] ?? 0);
            // Оба процента считаем от исходной суммы (как на фронте)
            $promoDiscount = $promoPercent > 0 ? round($originalAmount * $promoPercent / 100, 2) : 0.00;
            $discountAmount += $promoDiscount;
        }

        $totalAmount = round(max($originalAmount - $discountAmount, 0.01), 2);

        // Pack promo info for webhook
        $promoQuery = [];
        if ($promoData) {
            $promoQuery['promocode'] = $promoData['code'] ?? $promocodeParam;
            $promoQuery['promo_type'] = $promoData['type'] ?? '';
            if (($promoData['type'] ?? '') === 'free_access') {
                // encode map as id:days,id:days
                $pairs = collect($promoData['services'] ?? [])->map(function ($s) {
                    return ($s['id'] ?? 0) . ':' . ($s['free_days'] ?? 0);
                })->implode(',');
                $promoQuery['promo_free'] = $pairs;
            } else {
                $promoQuery['promo_percent'] = (int)($promoData['discount_percent'] ?? 0);
            }
        }

        $invoice = MonoPaymentService::createInvoice(
            amount: $totalAmount,
            redirectUrl: config('app.url') . '/checkout?success=true',
            webhookUrl: config('app.url') . '/api/mono/webhook?' . http_build_query(array_merge([
                'service_ids' => implode(',', $request->services),
                'trial_ids' => implode(',', $trialIds),
                'user_id' => $user->id,
            ], $promoQuery)),
            walletId: 'wallet_user_' . $user->id,
        );

        if (isset($invoice['pageUrl'])) {
            return \App\Http\Responses\ApiResponse::success(['url' => $invoice['pageUrl']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
    }

    public function webhook(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        Log::info('Mono Webhook received', $request->all());

        if ($request->status !== 'success') {
            return \App\Http\Responses\ApiResponse::success();
        }

        if ($this->invoiceAlreadyProcessed($request->invoiceId)) {
            return response()->json(['success' => false], 403);
        }

        // Проверяем, это пополнение баланса?
        if ($request->has('is_topup') && $request->is_topup == '1') {
            return $this->handleTopUpWebhook($request);
        }

        // Проверяем, это гостевой платеж?
        if ($request->has('is_guest') && $request->is_guest == '1') {
            return $this->handleGuestWebhook($request);
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $trialIds = array_filter(array_map('intval', explode(',', $request->trial_ids ?? '')));
        $serviceIds = array_filter(array_map('intval', explode(',', $request->service_ids ?? '')));
        $services = Service::with('translations')->whereIn('id', $serviceIds)->get();
        $trialDays = (int)Option::get('trial_days', 7);
        $currency = strtoupper(Option::get('currency'));
        $totalAmount = 0;

        // Parse promo
        $promoCode = trim((string)($request->promocode ?? ''));
        $promoType = trim((string)($request->promo_type ?? '')); 
        $promoFreeRaw = trim((string)($request->promo_free ?? ''));
        $promoPercent = (int)($request->promo_percent ?? 0);
        $promoFreeMap = collect();
        if ($promoType === 'free_access' && $promoFreeRaw !== '') {
            $promoFreeMap = collect(explode(',', $promoFreeRaw))->mapWithKeys(function ($pair) {
                [$sid, $days] = array_pad(explode(':', $pair), 2, 0);
                return [(int)$sid => (int)$days];
            });
        }

        foreach ($services as $service) {
            $isTrial = in_array($service->id, $trialIds, true);
            $amount = $isTrial ? ($service->trial_amount ?? 0) : $service->amount;
            // Apply promo on webhook: amount 0 for free_access covered
            if ($promoType === 'free_access' && $promoFreeMap->has($service->id)) {
                $amount = 0.00;
            }

            // Upsert subscription: extend existing or create new
            $existing = Subscription::where('user_id', $user->id)
                ->where('service_id', $service->id)
                ->orderByDesc('id')
                ->first();

            $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                ? Carbon::parse($existing->next_payment_at)
                : Carbon::now();

            if ($isTrial) {
                $nextAt = (clone $baseDate)->addDays($trialDays);
            } elseif ($promoType === 'free_access' && $promoFreeMap->has($service->id)) {
                $nextAt = (clone $baseDate)->addDays(max(0, (int)$promoFreeMap->get($service->id)));
            } else {
                $nextAt = (clone $baseDate)->addMonth();
            }

            if ($existing) {
                $existing->status = Subscription::STATUS_ACTIVE;
                $existing->payment_method = 'credit_card';
                $existing->is_auto_renew = 1;
                $existing->is_trial = $isTrial;
                $existing->next_payment_at = $nextAt;
                $existing->order_id = $request->invoiceId;
                $existing->save();
                $subId = $existing->id;
                $notifyDate = Carbon::parse($existing->next_payment_at);
            } else {
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'payment_method' => 'credit_card',
                    'service_id' => $service->id,
                    'is_auto_renew' => 1,
                    'next_payment_at' => $nextAt,
                    'is_trial' => $isTrial,
                    'order_id' => $request->invoiceId,
                ]);
                $subId = $subscription->id;
                $notifyDate = $subscription->next_payment_at;
            }

            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => 'credit_card',
                'subscription_id' => $subId,
            ]);

            $totalAmount += $amount;

            $this->notifyUserOnSubscription($user, $service, Carbon::parse($notifyDate));
        }

        EmailService::send('payment_confirmation', $user->id, [
            'amount' => number_format($totalAmount, 2, '.', '') . ' ' . $currency,
        ]);

        NotifierService::send(
            'payment',
            __('notifier.new_payment_title', array(
                'method' => 'Mono'
            )),
            __('notifier.new_payment_message', array(
                'method' => 'Mono',
                'email' => $user->email,
                'name' => $user->name
            ))
        );

        // Record promocode usage if present
        if ($promoCode !== '') {
            DB::transaction(function () use ($promoCode, $user, $request) {
                $promo = Promocode::where('code', $promoCode)->lockForUpdate()->first();
                if ($promo) {
                    PromocodeUsage::create([
                        'promocode_id' => $promo->id,
                        'user_id' => $user->id,
                        'order_id' => (string)$request->invoiceId,
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

    /**
     * Создание платежа для гостевой покупки (без авторизации)
     * Только для товаров, не для подписок
     */
    public function createGuestPayment(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        $request->validate([
            'guest_email' => 'required|email',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:service_accounts,id',
            'products.*.quantity' => 'required|integer|min:1',
            'promocode' => 'nullable|string',
        ]);

        $guestEmail = strtolower(trim($request->guest_email));

        // Рассчитываем общую стоимость товаров
        $productsData = [];
        $totalAmount = 0;

        foreach ($request->products as $productItem) {
            $product = ServiceAccount::find($productItem['id']);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }
            
            $quantity = $productItem['quantity'];
            $available = $product->getAvailableStock();
            
            if ($available < $quantity) {
                return response()->json([
                    'success' => false, 
                    'message' => "Insufficient stock for {$product->title}"
                ], 422);
            }
            
            $price = $product->getCurrentPrice();
            $itemTotal = $price * $quantity;
            $totalAmount += $itemTotal;
            
            $productsData[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $itemTotal,
            ];
        }

        // Apply promocode if provided
        $promoData = null;
        $promocodeParam = trim((string) $request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, null); // null = гость
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? 'Invalid promocode'], 422);
            }

            // Применяем скидку по промокоду
            if (($promoData['type'] ?? '') === 'discount') {
                $discountPercent = (int)($promoData['discount_percent'] ?? 0);
                $discountAmount = round($totalAmount * $discountPercent / 100, 2);
                $totalAmount = round($totalAmount - $discountAmount, 2);
            }
        }

        $totalAmount = max($totalAmount, 0.01); // Минимальная сумма

        // Создаем invoice через Mono
        $invoice = MonoPaymentService::createInvoice(
            amount: $totalAmount,
            redirectUrl: config('app.url') . '/checkout?success=true',
            webhookUrl: config('app.url') . '/api/mono/webhook?' . http_build_query([
                'is_guest' => '1',
                'guest_email' => $guestEmail,
                'products_data' => base64_encode(json_encode($productsData)),
                'promocode' => $promocodeParam,
            ]),
            walletId: 'wallet_guest_' . md5($guestEmail),
        );

        if (isset($invoice['pageUrl'])) {
            return \App\Http\Responses\ApiResponse::success(['url' => $invoice['pageUrl']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
    }

    private function invoiceAlreadyProcessed(string $invoiceId): bool
    {
        return Subscription::where('order_id', $invoiceId)->exists() ||
               Transaction::where('payment_method', 'credit_card')
                   ->where('status', 'completed')
                   ->where('created_at', '>', now()->subMinutes(5))
                   ->exists();
    }

    /**
     * Обработка webhook для пополнения баланса через банковскую карту
     * 
     * Этот метод вызывается платежной системой Monobank после успешной оплаты.
     * Используется BalanceService для безопасного зачисления средств на баланс.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    private function handleTopUpWebhook(Request $request): JsonResponse
    {
        // Получаем ID invoice из webhook
        $invoiceId = $request->invoiceId ?? null;
        if (!$invoiceId) {
            Log::error('Webhook пополнения баланса: отсутствует invoice_id', [
                'request' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Missing invoice_id'
            ], 400);
        }

        // Получаем пользователя
        $userId = $request->user_id;
        if (!$userId) {
            Log::error('Webhook пополнения баланса: отсутствует user_id', [
                'invoice_id' => $invoiceId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Missing user_id'
            ], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('Webhook пополнения баланса: пользователь не найден', [
                'user_id' => $userId,
                'invoice_id' => $invoiceId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Получаем и валидируем сумму
        $amount = round((float)$request->amount, 2);
        if ($amount <= 0) {
            Log::error('Webhook пополнения баланса: недопустимая сумма', [
                'amount' => $request->amount,
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid amount'
            ], 400);
        }

        try {
            // Используем BalanceService для безопасного пополнения баланса
            // BalanceService автоматически проверит дубликаты по invoice_id
            $balanceService = app(BalanceService::class);
            
            $balanceTransaction = $balanceService->topUp(
                user: $user,
                amount: $amount,
                type: BalanceService::TYPE_TOPUP_CARD,
                metadata: [
                    'invoice_id' => $invoiceId,
                    'payment_method' => 'monobank',
                    'payment_system' => 'monobank',
                    'webhook_received_at' => now()->toDateTimeString(),
                ]
            );

            // Проверяем, была ли операция выполнена или это дубликат
            if ($balanceTransaction) {
                Log::info('Баланс успешно пополнен через Monobank', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'amount' => $amount,
                    'invoice_id' => $invoiceId,
                    'balance_transaction_id' => $balanceTransaction->id,
                    'balance_after' => $balanceTransaction->balance_after,
                ]);

                // Отправляем уведомление администратору
                NotifierService::send(
                    'balance_topup',
                    'Баланс пополнен',
                    "Пользователь {$user->name} ({$user->email}) пополнил баланс на {$amount} " . Option::get('currency', 'USD') . " через Monobank"
                );

                return \App\Http\Responses\ApiResponse::success();
            }

            // Если вернулся null, значит операция уже была обработана ранее
            Log::info('Webhook пополнения баланса: дубликат операции', [
                'user_id' => $user->id,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ]);

            return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);

        } catch (\InvalidArgumentException $e) {
            Log::error('Webhook пополнения баланса: ошибка валидации', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Webhook пополнения баланса: критическая ошибка', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Processing failed'
            ], 500);
        }
    }

    /**
     * Обработка webhook для гостевого платежа
     */
    private function handleGuestWebhook(Request $request): JsonResponse
    {
        $guestEmail = trim((string)$request->guest_email);
        if (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Invalid guest email'], 400);
        }

        $productsDataEncoded = $request->products_data ?? '';
        $productsData = json_decode(base64_decode($productsDataEncoded), true);
        
        if (!is_array($productsData) || empty($productsData)) {
            return response()->json(['success' => false, 'message' => 'Invalid products data'], 400);
        }

        $promocode = trim((string)($request->promocode ?? ''));

        try {
            // Создаем покупки для гостя
            GuestCartController::createGuestPurchases($guestEmail, $productsData, $promocode);

            // Отправляем email уведомление гостю с информацией о покупке
            $totalAmount = array_sum(array_column($productsData, 'total'));
            \App\Services\EmailService::sendToGuest(
                $guestEmail,
                'guest_purchase_confirmation',
                [
                    'products_count' => count($productsData),
                    'total_amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency')),
                    'guest_email' => $guestEmail,
                ]
            );

            // Уведомление админу о новой гостевой покупке
            NotifierService::send(
                'guest_product_purchase',
                __('notifier.new_product_purchase_title', ['method' => 'Monobank']),
                __('notifier.new_product_purchase_message', [
                    'email' => $guestEmail,
                    'name' => 'Гость',
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ])
            );

            LoggingService::info('Guest purchase completed', [
                'guest_email' => $guestEmail,
                'invoice_id' => $request->invoiceId,
                'products_count' => count($productsData),
            ]);

            // Записываем использование промокода если есть
            if ($promocode !== '') {
                DB::transaction(function () use ($promocode, $guestEmail, $request) {
                    $promo = Promocode::where('code', $promocode)->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                            'promocode_id' => $promo->id,
                            'user_id' => null, // Гостевая покупка
                            'order_id' => (string)$request->invoiceId,
                        ]);
                        if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                            $promo->usage_count = (int)$promo->usage_count + 1;
                            $promo->save();
                        }
                    }
                });
            }

            return \App\Http\Responses\ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('Guest webhook processing failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail,
            ]);
            return response()->json(['success' => false, 'message' => 'Processing failed'], 500);
        }
    }

    /**
     * Создание платежа для пополнения баланса через банковскую карту
     * 
     * Этот метод создает invoice в платежной системе Monobank для пополнения баланса.
     * После успешной оплаты средства автоматически зачислятся через webhook.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createTopUpPayment(Request $request): JsonResponse
    {
        // Валидация входных данных
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
        ]);

        // Получаем авторизованного пользователя
        $user = $this->getApiUser($request);
        if (!$user) {
            Log::warning('Попытка пополнения баланса без авторизации', [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        // Округляем сумму до 2 знаков после запятой
        $amount = round((float)$validated['amount'], 2);

        // Проверка разумности суммы (защита от ошибок ввода)
        if ($amount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Минимальная сумма пополнения: 1 ' . Option::get('currency', 'USD')
            ], 422);
        }

        try {
            // Создаем invoice в платежной системе Monobank
            $invoice = MonoPaymentService::createInvoice(
                amount: $amount,
                redirectUrl: config('app.url') . '/profile?topup=success',
                webhookUrl: config('app.url') . '/api/mono/webhook?' . http_build_query([
                    'is_topup' => '1',
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]),
                walletId: 'wallet_topup_' . $user->id . '_' . time(),
            );

            // Проверяем, что invoice создан успешно
            if (isset($invoice['pageUrl']) && isset($invoice['invoiceId'])) {
                Log::info('Создан платеж для пополнения баланса', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'amount' => $amount,
                    'currency' => Option::get('currency', 'USD'),
                    'invoice_id' => $invoice['invoiceId'],
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'url' => $invoice['pageUrl'],
                    'invoice_id' => $invoice['invoiceId'],
                ]);
            }

            // Если invoice не создан, возвращаем ошибку
            Log::error('Не удалось создать invoice для пополнения баланса', [
                'user_id' => $user->id,
                'amount' => $amount,
                'response' => $invoice,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать платеж. Попробуйте позже.'
            ], 422);

        } catch (\Exception $e) {
            Log::error('Ошибка при создании платежа для пополнения баланса', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при создании платежа'
            ], 500);
        }
    }

    private function notifyUserOnSubscription(User $user, Service $service, Carbon $nextPaymentDate): void
    {
        app(NotificationTemplateService::class)->sendToUser($user, 'purchase', [
            'service' => $service->code,
            'date' => $nextPaymentDate->format('d.m.Y'),
        ]);

        $serviceName = $service->getTranslation('name', $user->lang ?? 'en') ?? env('APP_NAME');

        EmailService::send('subscription_activated', $user->id, [
            'service_name' => $serviceName,
        ]);
        NotifierService::send(
            'subscription_activated',
            __('notifier.new_subscription_title', array(
                'method' => 'Mono'
            )),
            __('notifier.new_subscription_message', array(
                'method' => 'Mono',
                'email' => $user->email,
                'name' => $user->name
            ))
        );
    }
}
