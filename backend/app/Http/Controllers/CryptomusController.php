<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Option;
use App\Models\User;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Models\ServiceAccount;
use App\Models\Purchase;
use App\Http\Controllers\GuestCartController;
use App\Services\NotifierService;
use App\Services\BalanceService;
use App\Services\ProductPurchaseService;
use Cryptomus\Api\RequestBuilderException;
use FunnyDev\Cryptomus\CryptomusSdk;
use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\PromocodeValidationService;
use App\Services\LoggingService;
use Illuminate\Support\Facades\DB;

class CryptomusController extends Controller
{
    /**
     * Создание платежа для авторизованного пользователя (покупка товаров)
     */
    public function createPayment(Request $request, PromocodeValidationService $promoService, ProductPurchaseService $purchaseService)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:service_accounts,id',
            'products.*.quantity' => 'required|integer|min:1',
            'promocode' => 'nullable|string',
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Подготавливаем данные о товарах используя сервис
        $prepareResult = $purchaseService->prepareProductsData($request->products);
        if (!$prepareResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $prepareResult['message']
            ], 422);
        }

        $productsData = $prepareResult['data'];
        $productsTotal = $prepareResult['total'];

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

        $totalAmount = $productsTotal;

        // Применяем персональную скидку пользователя (если есть и активна)
        $personalDiscountPercent = $user->getActivePersonalDiscount();
        if ($personalDiscountPercent > 0) {
            $totalAmount = $totalAmount - ($totalAmount * $personalDiscountPercent / 100);
        }

        // Применяем скидку по промокоду если есть (применяется после персональной скидки)
        if ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $discountPercent = floatval($promoData['discount_percent'] ?? 0);
            $totalAmount = $totalAmount - ($totalAmount * $discountPercent / 100);
        }

        $totalAmount = max(round($totalAmount, 2), 0.01); // Минимальная сумма

        $orderId = 'order_' . $user->id . '_' . time();
        $sdk = new CryptomusSdk();

        // Подготавливаем данные для webhook
        $productsDataForWebhook = collect($productsData)->map(function($item) {
            return [
                'product_id' => $item['product']->id,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total'],
            ];
        })->toArray();

        $webhookParams = [
            'user_id' => $user->id,
            'products_data' => base64_encode(json_encode($productsDataForWebhook)),
        ];

        if ($promocodeParam !== '') {
            $webhookParams['promocode'] = $promocodeParam;
        }

        try {
            $response = $sdk->create_payment(
                $orderId,
                $totalAmount,
                Option::get('currency'),
                '',
                '',
                config('app.url') . '/checkout',
                config('app.url') . '/api/cryptomus/webhook?' . http_build_query($webhookParams),
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
        } catch (\Exception $e) {
            \Log::error('Cryptomus payment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Payment creation failed'], 500);
        }
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

            // Проверяем, это покупка товаров для авторизованного пользователя?
            if ($request->has('user_id') && $request->has('products_data')) {
                return $this->handleUserPurchaseWebhook($request, $data);
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
                return \App\Http\Responses\ApiResponse::success(['url' => $response]);
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
     * Обработка webhook для покупки товаров авторизованным пользователем
     */
    private function handleUserPurchaseWebhook(Request $request, array $data)
    {
        $userId = $request->user_id;
        if (!$userId) {
            \Log::error('Invalid user_id in user purchase webhook');
            return response('Invalid user_id', 400);
        }

        $user = User::find($userId);
        if (!$user) {
            \Log::error('User not found in webhook', ['user_id' => $userId]);
            return response('User not found', 404);
        }

        $productsDataEncoded = $request->products_data ?? '';
        $productsData = json_decode(base64_decode($productsDataEncoded), true);
        
        if (!is_array($productsData) || empty($productsData)) {
            \Log::error('Invalid products data in user purchase webhook');
            return response('Invalid products data', 400);
        }

        $promocode = trim((string)($request->promocode ?? ''));

        try {
            $purchaseService = app(ProductPurchaseService::class);
            
            // Подготавливаем данные о товарах для создания покупок
            $preparedProductsData = [];
            foreach ($productsData as $item) {
                $product = ServiceAccount::find($item['product_id']);
                if (!$product) {
                    \Log::warning('Product not found in webhook', ['product_id' => $item['product_id']]);
                    continue;
                }
                
                $preparedProductsData[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ];
            }
            
            if (empty($preparedProductsData)) {
                \Log::error('No valid products found in webhook');
                return response('No valid products', 400);
            }
            
            // Создаем покупки для авторизованного пользователя
            $purchases = $purchaseService->createMultiplePurchases(
                $preparedProductsData,
                $user->id,
                null, // guest_email = null для авторизованных
                'crypto'
            );

            // Отправляем email уведомление пользователю
            $totalAmount = array_sum(array_column($productsData, 'total'));
            EmailService::send('payment_confirmation', $user->id, [
                'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
            ]);

            // Уведомление админу о новой покупке
            NotifierService::send(
                'product_purchase',
                __('notifier.new_product_purchase_title', ['method' => 'Cryptomus']),
                __('notifier.new_product_purchase_message', [
                    'email' => $user->email,
                    'name' => $user->name,
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ])
            );

            LoggingService::info('User purchase completed via Cryptomus', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'order_id' => $data['order_id'] ?? 'unknown',
                'products_count' => count($productsData),
            ]);

            // Записываем использование промокода если есть
            if ($promocode !== '') {
                DB::transaction(function () use ($promocode, $user, $data) {
                    $promo = Promocode::where('code', $promocode)->lockForUpdate()->first();
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

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('User purchase webhook processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
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
                __('notifier.new_product_purchase_title', ['method' => 'Cryptomus']),
                __('notifier.new_product_purchase_message', [
                    'email' => $guestEmail,
                    'name' => 'Гость',
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ])
            );

            LoggingService::info('Guest purchase completed via Cryptomus', [
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
