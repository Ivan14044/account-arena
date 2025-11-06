<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Option;
use App\Models\User;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Models\ServiceAccount;
use App\Models\Purchase;
use App\Http\Controllers\GuestCartController;
use App\Services\NotificationTemplateService;
use App\Services\NotifierService;
use App\Services\BalanceService;
use Carbon\Carbon;
use Cryptomus\Api\RequestBuilderException;
use FunnyDev\Cryptomus\CryptomusSdk;
use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\PromocodeValidationService;
use Illuminate\Support\Facades\DB;

class CryptomusController extends Controller
{
    public function createPayment(Request $request, PromocodeValidationService $promoService)
    {
        $request->validate([
            'services' => 'required|array|min:1',
            'services.*' => 'integer',
            'promocode' => 'nullable|string',
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $services = Service::whereIn('id', $request->services)->get();
        if ($services->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid services',
            ], 422);
        }

        $originalAmount = $services->sum('amount');

        $discount2 = (int)Option::get('discount_2', 0);
        $discount3 = (int)Option::get('discount_3', 0);

        $count = $services->count();

        $appliedDiscountPercent = 0;
        if ($count >= 3 && $discount3 > 0) {
            $appliedDiscountPercent = $discount3;
        } elseif ($count >= 2 && $discount2 > 0) {
            $appliedDiscountPercent = $discount2;
        }

        // Apply promocode if provided
        $promoData = null;
        $promocodeParam = trim((string) $request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, $user->id);

            if (!($promoData['ok'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $promoData['message'] ?? __('promocodes.invalid')
                ], 422);
            }
        }

        $discountAmount = round($originalAmount * $appliedDiscountPercent / 100, 2);

        if ($promoData && ($promoData['type'] ?? '') === 'free_access') {
            $freeMap = collect($promoData['services'] ?? [])->keyBy('id');
            $originalAmount = round($services->sum(function ($s) use ($freeMap) {
                return $freeMap->has($s->id) ? 0.00 : $s->amount;
            }), 2);

            $discountAmount = round($originalAmount * $appliedDiscountPercent / 100, 2);
        } elseif ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $promoPercent = (int)($promoData['discount_percent'] ?? 0);
            // Оба процента считаем от исходной суммы (как на фронте)
            $promoDiscount = $promoPercent > 0 ? round($originalAmount * $promoPercent / 100, 2) : 0.00;
            $discountAmount += $promoDiscount;
        }

        $totalAmount = round($originalAmount - $discountAmount, 2);

        if ($totalAmount <= 0) {
            $totalAmount = 0.01;
        }

        $orderId = 'order_' . $user->id . '_' . time();
        $sdk = new CryptomusSdk();

        $promoQuery = [];
        if ($promoData) {
            $promoQuery['promocode'] = $promoData['code'] ?? $promocodeParam;
            $promoQuery['promo_type'] = $promoData['type'] ?? '';
            if (($promoData['type'] ?? '') === 'free_access') {
                $pairs = collect($promoData['services'] ?? [])->map(function ($s) {
                    return ($s['id'] ?? 0) . ':' . ($s['free_days'] ?? 0);
                })->implode(',');
                $promoQuery['promo_free'] = $pairs;
            } else {
                $promoQuery['promo_percent'] = (int)($promoData['discount_percent'] ?? 0);
            }
        }

        $response = $sdk->create_payment(
            $orderId,
            $totalAmount,
            Option::get('currency'),
            '',
            '',
            config('app.url') . '/checkout',
            config('app.url') . '/api/cryptomus/webhook?service_ids=' . implode(',', $request->services) . '&user_id=' . $user->id . (count($promoQuery) ? '&' . http_build_query($promoQuery) : ''),
            config('app.url') . '/checkout?success=true',
        );

        if ($response) {
            return response()->json([
                'success' => true,
                'url' => $response,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to create payment',
        ], 422);
    }

    /**
     * @throws RequestBuilderException
     */
    public function webhook(Request $request)
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);

        \Log::info('Webhook received', $data);

        if (!is_array($data)) {
            \Log::error('Invalid JSON');
            return response('Invalid JSON', 400);
        }

        $cryptomus = new CryptomusSdk();
        $result = $cryptomus->read_result($data);
        \Log::info('Webhook check - ', $result);

        /*
         * You could handle the response of transaction here like:
         * if ($result['status']) {approve order for use or email them...} else {notice them the $result['message']}
         * if $result['message'] is "Trying to fake payment result" then you should block your user!
         * You could get 2 integer variables Session::get('cryptomus_hacked') & Session::get('cryptomus_hacked') to decide what to do with your user.
         */

        if ($result['status'] == 'paid') {
            // Проверяем, это пополнение баланса?
            if ($request->has('is_topup') && $request->is_topup == '1') {
                return $this->handleTopUpWebhook($request, $data);
            }

            // Проверяем, это гостевой платеж?
            if ($request->has('is_guest') && $request->is_guest == '1') {
                return $this->handleGuestWebhook($request, $data);
            }

            if (Subscription::where('order_id', $data['order_id'])->exists()) {
                return response('OK', 200);
            }

            $nextPaymentDate = Carbon::now()->addMonth();
            $user = User::find($request->user_id);
            $totalAmount = 0;
            $serviceIds = explode(',', $request->service_ids);
            $services = Service::with('translations')->whereIn('id', $serviceIds)->get();

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
                $existing = Subscription::where('user_id', $user->id)
                    ->where('service_id', $service->id)
                    ->orderByDesc('id')
                    ->first();

                $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                    ? Carbon::parse($existing->next_payment_at)
                    : $nextPaymentDate;

                $nextAt = ($promoType === 'free_access' && $promoFreeMap->has($service->id))
                    ? (clone $baseDate)->addDays(max(0, (int)$promoFreeMap->get($service->id)))
                    : $baseDate;

                if ($existing) {
                    $existing->status = Subscription::STATUS_ACTIVE;
                    $existing->payment_method = 'crypto';
                    $existing->is_auto_renew = 0;
                    $existing->next_payment_at = $nextAt;
                    $existing->order_id = $data['order_id'];
                    $existing->save();
                    $subId = $existing->id;
                } else {
                    $subscription = Subscription::create([
                        'user_id' => $user->id,
                        'status' => Subscription::STATUS_ACTIVE,
                        'payment_method' => 'crypto',
                        'service_id' => $service->id,
                        'is_auto_renew' => 0,
                        'next_payment_at' => $nextAt,
                        'order_id' => $data['order_id']
                    ]);
                    $subId = $subscription->id;
                }

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => ($promoType === 'free_access' && $promoFreeMap->has($service->id)) ? 0.00 : $service->amount,
                    'currency' => Option::get('currency'),
                    'payment_method' => 'crypto',
                    'subscription_id' => $subId
                ]);

                $totalAmount += $service->amount;

                app(NotificationTemplateService::class)->sendToUser($user, 'purchase', [
                    'service' => $service->code,
                    'date' => $nextPaymentDate->format('d.m.Y'),
                ]);

                $serviceName = $service?->getTranslation('name', $user->lang ?? 'en') ?? $service?->name;

                EmailService::send('subscription_activated', $user->id, [
                    'service_name' => $serviceName
                ]);
            }

            EmailService::send('payment_confirmation', $user->id, [
                'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
            ]);

            NotifierService::send(
                'payment',
                __('notifier.new_payment_title', array(
                    'method' => 'Crypto'
                )),
                __('notifier.new_payment_message', array(
                    'method' => 'Crypto',
                    'email' => $user->email,
                    'name' => $user->name
                ))
            );
            // Record promocode usage if present
            if ($promoCode !== '') {
                DB::transaction(function () use ($promoCode, $user, $data) {
                    $promo = Promocode::where('code', $promoCode)->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                                'promocode_id' => $promo->id,
                                'user_id' => $user->id,
                                'order_id' => (string)($data['order_id'] ?? ''),
                        ]);
                        
                        if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                            $promo->usage_count = (int)$promo->usage_count + 1;
                            $promo->save();
                        }
                    }
                });
            }
        }

        return response('OK', 200);
    }

    /**
     * Создание платежа для гостевой покупки (без авторизации)
     * Только для товаров, не для подписок
     */
    public function createGuestPayment(Request $request, PromocodeValidationService $promoService)
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

        $orderId = 'guest_order_' . time() . '_' . md5($guestEmail);
        $sdk = new CryptomusSdk();

        try {
            $response = $sdk->create_payment(
                $orderId,
                $totalAmount,
                Option::get('currency'),
                '',
                '',
                config('app.url') . '/checkout',
                config('app.url') . '/api/cryptomus/webhook?' . http_build_query([
                    'is_guest' => '1',
                    'guest_email' => $guestEmail,
                    'products_data' => base64_encode(json_encode($productsData)),
                    'promocode' => $promocodeParam,
                ]),
                config('app.url') . '/checkout?success=true',
            );

            if ($response) {
                return response()->json(['success' => true, 'url' => $response]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
        } catch (\Exception $e) {
            \Log::error('Guest Cryptomus payment creation failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail,
            ]);
            return response()->json(['success' => false, 'message' => 'Payment creation failed'], 500);
        }
    }

    /**
     * Создание платежа для пополнения баланса через криптовалюту
     * 
     * Этот метод создает invoice в Cryptomus для пополнения баланса.
     * После успешной оплаты средства автоматически зачислятся через webhook.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTopUpPayment(Request $request)
    {
        // Валидация входных данных
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
        ]);

        // Получаем авторизованного пользователя
        $user = $this->getApiUser($request);
        if (!$user) {
            \Log::warning('Попытка пополнения баланса через крипту без авторизации', [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        // Округляем сумму до 2 знаков после запятой
        $amount = round((float)$validated['amount'], 2);

        // Проверка разумности суммы
        if ($amount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Минимальная сумма пополнения: 1 ' . Option::get('currency', 'USD')
            ], 422);
        }

        // Генерируем уникальный ID заказа
        $orderId = 'topup_crypto_' . $user->id . '_' . time() . '_' . bin2hex(random_bytes(4));
        
        try {
            // Создаем платеж в Cryptomus
            $sdk = new CryptomusSdk();
            
            $response = $sdk->create_payment(
                $orderId,
                $amount,
                Option::get('currency', 'USD'),
                '',  // Дополнительная информация
                '',  // Email (необязательно)
                config('app.url') . '/profile',  // Fallback URL
                config('app.url') . '/api/cryptomus/webhook?' . http_build_query([
                    'is_topup' => '1',
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]),
                config('app.url') . '/profile?topup=success',  // Success URL
            );

            // Проверяем, что платеж создан успешно
            if ($response) {
                \Log::info('Создан платеж для пополнения баланса через криптовалюту', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'amount' => $amount,
                    'currency' => Option::get('currency', 'USD'),
                    'order_id' => $orderId,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'url' => $response,
                    'order_id' => $orderId,
                ]);
            }

            // Если платеж не создан, возвращаем ошибку
            \Log::error('Не удалось создать платеж в Cryptomus для пополнения баланса', [
                'user_id' => $user->id,
                'amount' => $amount,
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать платеж. Попробуйте позже.'
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Ошибка при создании платежа через Cryptomus', [
                'user_id' => $user->id,
                'amount' => $amount,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при создании платежа'
            ], 500);
        }
    }

    /**
     * Обработка webhook для пополнения баланса через криптовалюту
     * 
     * Этот метод вызывается платежной системой Cryptomus после успешной оплаты.
     * Используется BalanceService для безопасного зачисления средств на баланс.
     * 
     * @param Request $request
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    private function handleTopUpWebhook(Request $request, array $data)
    {
        // Получаем ID заказа из webhook
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) {
            \Log::error('Webhook пополнения баланса (crypto): отсутствует order_id', [
                'data' => $data,
            ]);
            return response('Missing order_id', 400);
        }

        // Получаем пользователя
        $userId = $request->user_id;
        if (!$userId) {
            \Log::error('Webhook пополнения баланса (crypto): отсутствует user_id', [
                'order_id' => $orderId,
            ]);
            return response('Missing user_id', 400);
        }

        $user = User::find($userId);
        if (!$user) {
            \Log::error('Webhook пополнения баланса (crypto): пользователь не найден', [
                'user_id' => $userId,
                'order_id' => $orderId,
            ]);
            return response('User not found', 404);
        }

        // Получаем и валидируем сумму
        $amount = round((float)$request->amount, 2);
        if ($amount <= 0) {
            \Log::error('Webhook пополнения баланса (crypto): недопустимая сумма', [
                'amount' => $request->amount,
                'order_id' => $orderId,
                'user_id' => $userId,
            ]);
            return response('Invalid amount', 400);
        }

        try {
            // Используем BalanceService для безопасного пополнения баланса
            // BalanceService автоматически проверит дубликаты по order_id
            $balanceService = app(BalanceService::class);
            
            $balanceTransaction = $balanceService->topUp(
                user: $user,
                amount: $amount,
                type: BalanceService::TYPE_TOPUP_CRYPTO,
                metadata: [
                    'order_id' => $orderId,
                    'payment_method' => 'cryptomus',
                    'payment_system' => 'cryptomus',
                    'cryptocurrency' => $data['currency'] ?? 'unknown',
                    'network' => $data['network'] ?? 'unknown',
                    'webhook_received_at' => now()->toDateTimeString(),
                ]
            );

            // Проверяем, была ли операция выполнена или это дубликат
            if ($balanceTransaction) {
                \Log::info('Баланс успешно пополнен через Cryptomus', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'amount' => $amount,
                    'order_id' => $orderId,
                    'balance_transaction_id' => $balanceTransaction->id,
                    'balance_after' => $balanceTransaction->balance_after,
                    'cryptocurrency' => $data['currency'] ?? 'unknown',
                ]);

                // Отправляем уведомление администратору
                NotifierService::send(
                    'balance_topup',
                    'Баланс пополнен',
                    "Пользователь {$user->name} ({$user->email}) пополнил баланс на {$amount} " . Option::get('currency', 'USD') . " через криптовалюту"
                );

                return response('OK', 200);
            }

            // Если вернулся null, значит операция уже была обработана ранее
            \Log::info('Webhook пополнения баланса (crypto): дубликат операции', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            return response('Already processed', 200);

        } catch (\InvalidArgumentException $e) {
            \Log::error('Webhook пополнения баланса (crypto): ошибка валидации', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'order_id' => $orderId,
                'amount' => $amount,
            ]);
            return response($e->getMessage(), 400);

        } catch (\Exception $e) {
            \Log::error('Webhook пополнения баланса (crypto): критическая ошибка', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'order_id' => $orderId,
                'amount' => $amount,
            ]);
            return response('Processing failed', 500);
        }
    }

    /**
     * Обработка webhook для гостевого платежа
     */
    private function handleGuestWebhook(Request $request, array $data)
    {
        $guestEmail = trim((string)$request->guest_email);
        if (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            \Log::error('Invalid guest email in webhook', ['guest_email' => $guestEmail]);
            return response('Invalid guest email', 400);
        }

        $productsDataEncoded = $request->products_data ?? '';
        $productsData = json_decode(base64_decode($productsDataEncoded), true);
        
        if (!is_array($productsData) || empty($productsData)) {
            \Log::error('Invalid products data in webhook');
            return response('Invalid products data', 400);
        }

        $promocode = trim((string)($request->promocode ?? ''));

        try {
            // Создаем покупки для гостя
            GuestCartController::createGuestPurchases($guestEmail, $productsData, $promocode);

            \Log::info('Guest purchase completed via Cryptomus', [
                'guest_email' => $guestEmail,
                'order_id' => $data['order_id'] ?? 'unknown',
                'products_count' => count($productsData),
            ]);

            // Записываем использование промокода если есть
            if ($promocode !== '') {
                DB::transaction(function () use ($promocode, $guestEmail, $data) {
                    $promo = Promocode::where('code', $promocode)->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                            'promocode_id' => $promo->id,
                            'user_id' => null, // Гостевая покупка
                            'order_id' => (string)($data['order_id'] ?? ''),
                        ]);
                        if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                            $promo->usage_count = (int)$promo->usage_count + 1;
                            $promo->save();
                        }
                    }
                });
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Guest webhook processing failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail,
            ]);
            return response('Processing failed', 500);
        }
    }
}
