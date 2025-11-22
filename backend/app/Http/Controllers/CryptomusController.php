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
use App\Services\NotificationTemplateService;
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
     * Handle Cryptomus webhook
     * Signature verification is handled by VerifyWebhookSignature middleware
     * 
     * @throws RequestBuilderException
     */
    public function webhook(Request $request)
    {
        $rawData = $request->getContent();
        $data = json_decode($rawData, true);

        if (!is_array($data)) {
            \Log::error('Cryptomus webhook: Invalid JSON', ['raw' => substr($rawData, 0, 200)]);
            return response('Invalid JSON', 400);
        }

        // Verify signature using SDK (middleware already checks header, but SDK checks body signature)
        $sdk = new CryptomusSdk();
        $result = $sdk->read_result($data);

        if (!isset($result['status']) || $result['status'] !== 'paid') {
            \Log::info('Cryptomus webhook: Payment not paid', [
                'status' => $result['status'] ?? 'unknown',
                'order_id' => $data['order_id'] ?? null
            ]);
            return response('OK', 200);
        }

        // Route to appropriate handler based on webhook parameters
        if ($request->has('is_topup') && $request->is_topup == '1') {
            return $this->handleTopUpWebhook($request, $data);
        }

        if ($request->has('is_guest') && $request->is_guest == '1') {
            return $this->handleGuestWebhook($request, $data);
        }

        if ($request->has('user_id') && $request->has('products_data')) {
            return $this->handleUserPurchaseWebhook($request, $data);
        }

        \Log::warning('Cryptomus webhook: Unknown webhook type', [
            'query_params' => $request->query(),
            'order_id' => $data['order_id'] ?? null
        ]);

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
     * Handle top-up webhook
     */
    private function handleTopUpWebhook(Request $request, array $data)
    {
        $orderId = $data['order_id'] ?? null;
        $userId = $request->user_id;

        if (!$orderId || !$userId) {
            \Log::error('Top-up webhook: Missing required parameters', [
                'has_order_id' => !empty($orderId),
                'has_user_id' => !empty($userId)
            ]);
            return response('Missing required parameters', 400);
        }

        $user = User::find($userId);
        if (!$user) {
            \Log::error('Top-up webhook: User not found', ['user_id' => $userId]);
            return response('User not found', 404);
        }

        $amount = round((float)$request->amount, 2);
        if ($amount <= 0) {
            \Log::error('Top-up webhook: Invalid amount', ['amount' => $request->amount]);
            return response('Invalid amount', 400);
        }

        try {
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

            if ($balanceTransaction) {
                \Log::info('Balance topped up via Cryptomus', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'order_id' => $orderId,
                    'balance_after' => $balanceTransaction->balance_after,
                ]);

                NotifierService::send(
                    'balance_topup',
                    'Баланс пополнен',
                    "Пользователь {$user->name} ({$user->email}) пополнил баланс на {$amount} " . Option::get('currency', 'USD') . " через криптовалюту"
                );

                return response('OK', 200);
            }

            \Log::info('Top-up webhook: Duplicate transaction', ['order_id' => $orderId]);
            return response('Already processed', 200);

        } catch (\InvalidArgumentException $e) {
            \Log::error('Top-up webhook: Validation error', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            return response($e->getMessage(), 400);
        } catch (\Exception $e) {
            \Log::error('Top-up webhook: Processing failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            return response('Processing failed', 500);
        }
    }

    /**
     * Handle user purchase webhook
     */
    private function handleUserPurchaseWebhook(Request $request, array $data)
    {
        $userId = $request->user_id;
        if (!$userId) {
            \Log::error('User purchase webhook: Missing user_id');
            return response('Missing user_id', 400);
        }

        $user = User::find($userId);
        if (!$user) {
            \Log::error('User purchase webhook: User not found', ['user_id' => $userId]);
            return response('User not found', 404);
        }

        $productsData = $this->parseProductsData($request->products_data ?? '');
        if (empty($productsData)) {
            \Log::error('User purchase webhook: Invalid products data');
            return response('Invalid products data', 400);
        }

        $promocode = trim((string)($request->promocode ?? ''));

        try {
            $preparedProductsData = $this->prepareProductsForPurchase($productsData);
            if (empty($preparedProductsData)) {
                \Log::error('User purchase webhook: No valid products');
                return response('No valid products', 400);
            }
            
            $purchaseService = app(ProductPurchaseService::class);
            $purchases = $purchaseService->createMultiplePurchases(
                $preparedProductsData,
                $user->id,
                null,
                'crypto'
            );

            $totalAmount = array_sum(array_column($productsData, 'total'));
            
            // Send notifications
            $this->sendPurchaseNotifications($user, $totalAmount, $purchases, false);

            $this->recordPromocodeUsage($promocode, $user->id, $data['order_id'] ?? '');

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('User purchase webhook: Processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return response('Processing failed', 500);
        }
    }

    /**
     * Handle guest purchase webhook
     */
    private function handleGuestWebhook(Request $request, array $data)
    {
        $guestEmail = trim((string)$request->guest_email);
        if (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            \Log::error('Guest webhook: Invalid email', ['email' => $guestEmail]);
            return response('Invalid guest email', 400);
        }

        $productsData = $this->parseProductsData($request->products_data ?? '');
        if (empty($productsData)) {
            \Log::error('Guest webhook: Invalid products data');
            return response('Invalid products data', 400);
        }

        $promocode = trim((string)($request->promocode ?? ''));

        try {
            GuestCartController::createGuestPurchases($guestEmail, $productsData, $promocode);

            $totalAmount = array_sum(array_column($productsData, 'total'));
            
            // Send guest notifications
            EmailService::sendToGuest(
                $guestEmail,
                'guest_purchase_confirmation',
                [
                    'products_count' => count($productsData),
                    'total_amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency')),
                    'guest_email' => $guestEmail,
                ]
            );

            NotifierService::sendFromTemplate(
                'guest_product_purchase',
                'admin_product_purchase',
                [
                    'method' => 'Cryptomus',
                    'email' => $guestEmail,
                    'name' => 'Гость',
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ]
            );

            $this->recordPromocodeUsage($promocode, null, $data['order_id'] ?? '');

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Guest webhook: Processing failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail
            ]);
            return response('Processing failed', 500);
        }
    }

    /**
     * Parse products data from base64 encoded string
     */
    private function parseProductsData(string $encodedData): array
    {
        if (empty($encodedData)) {
            return [];
        }

        $decoded = json_decode(base64_decode($encodedData), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Prepare products data for purchase creation
     */
    private function prepareProductsForPurchase(array $productsData): array
    {
        $prepared = [];
        foreach ($productsData as $item) {
            $product = ServiceAccount::find($item['product_id'] ?? null);
            if (!$product) {
                \Log::warning('Product not found in webhook', [
                    'product_id' => $item['product_id'] ?? null
                ]);
                continue;
            }
            
            $prepared[] = [
                'product' => $product,
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'total' => $item['total'] ?? 0,
            ];
        }
        
        return $prepared;
    }

    /**
     * Send purchase notifications to user and admin
     */
    private function sendPurchaseNotifications($user, float $totalAmount, array $purchases, bool $isGuest): void
    {
        if ($isGuest) {
            return;
        }

        // Email to user
        EmailService::send('payment_confirmation', $user->id, [
            'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
        ]);

        // Notification to user
        if (!empty($purchases) && isset($purchases[0]) && $purchases[0]->order_number) {
            $notificationService = app(NotificationTemplateService::class);
            $notificationService->sendToUser($user, 'purchase', [
                'order_number' => $purchases[0]->order_number,
            ]);
        }

        // Notification to admin
        NotifierService::sendFromTemplate(
            'product_purchase',
            'admin_product_purchase',
            [
                'method' => 'Cryptomus',
                'email' => $user->email,
                'name' => $user->name,
                'products' => count($purchases),
                'amount' => number_format($totalAmount, 2),
            ]
        );

        LoggingService::info('Purchase completed via Cryptomus', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'products_count' => count($purchases),
        ]);
    }

    /**
     * Record promocode usage
     */
    private function recordPromocodeUsage(string $promocode, ?int $userId, string $orderId): void
    {
        if (empty($promocode)) {
            return;
        }

        try {
            DB::transaction(function () use ($promocode, $userId, $orderId) {
                $promo = Promocode::where('code', $promocode)->lockForUpdate()->first();
                if (!$promo) {
                    return;
                }

                PromocodeUsage::create([
                    'promocode_id' => $promo->id,
                    'user_id' => $userId,
                    'order_id' => (string)$orderId,
                ]);

                if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                    $promo->increment('usage_count');
                }
            });
        } catch (\Exception $e) {
            \Log::error('Failed to record promocode usage', [
                'promocode' => $promocode,
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
