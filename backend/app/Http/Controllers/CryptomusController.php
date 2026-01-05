<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Option;
use App\Models\User;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Models\ServiceAccount;
use App\Http\Controllers\GuestCartController;
use App\Services\NotifierService;
use App\Services\BalanceService;
use App\Services\ProductPurchaseService;
use App\Services\NotificationTemplateService;
use App\Services\EmailService;
use App\Services\PromocodeValidationService;
use App\Services\LoggingService;
use FunnyDev\Cryptomus\CryptomusSdk;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CryptomusController extends Controller
{
    /**
     * Create payment for authenticated user (product purchase)
     */
    public function createPayment(Request $request, PromocodeValidationService $promoService, ProductPurchaseService $purchaseService): JsonResponse
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

        $prepareResult = $purchaseService->prepareProductsData($request->products);
        if (!$prepareResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $prepareResult['message']
            ], 422);
        }

        $productsData = $prepareResult['data'];
        $productsTotal = $prepareResult['total'];

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

        $personalDiscountPercent = $user->getActivePersonalDiscount();
        $promoDiscountPercent = 0;
        
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

        $totalAmount = max(round($totalAmount, 2), 0.01);

        $orderId = 'order_' . $user->id . '_' . time();
        $sdk = new CryptomusSdk();

        $productsDataForWebhook = collect($productsData)->map(function($item) {
            return [
                'product_id' => $item['product']->id,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total'],
            ];
        })->toArray();

        $paymentMetadata = [
            'payment_type' => 'user',
            'user_id' => $user->id,
            'products_data' => $productsDataForWebhook,
        ];

        if ($promocodeParam !== '') {
            $paymentMetadata['promocode'] = $promocodeParam;
        }

        try {
            $response = $sdk->create_payment(
                $orderId,
                $totalAmount,
                Option::get('currency'),
                '',
                '',
                config('app.url') . '/checkout',
                config('app.url') . '/api/cryptomus/webhook',
                config('app.url') . '/order-success',
            );

            if ($response) {
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $totalAmount,
                    'currency' => Option::get('currency', 'USD'),
                    'payment_method' => 'cryptomus',
                    'status' => 'pending',
                    'metadata' => [
                        'order_id' => $orderId,
                        ...$paymentMetadata,
                    ],
                ]);

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
            Log::error('Cryptomus payment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Payment creation failed'], 500);
        }
    }

    /**
     * Create payment for guest purchase (without authentication)
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

        $promoData = null;
        $promocodeParam = trim((string) $request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, null);
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? 'Invalid promocode'], 422);
            }

            if (($promoData['type'] ?? '') === 'discount') {
                $discountPercent = (int)($promoData['discount_percent'] ?? 0);
                $discountAmount = round($totalAmount * $discountPercent / 100, 2);
                $totalAmount = round($totalAmount - $discountAmount, 2);
            }
        }

        $totalAmount = max($totalAmount, 0.01);

        $orderId = 'guest_order_' . time() . '_' . md5($guestEmail);
        $sdk = new CryptomusSdk();

        $paymentMetadata = [
            'payment_type' => 'guest',
            'guest_email' => $guestEmail,
            'products_data' => $productsData,
        ];

        if ($promocodeParam !== '') {
            $paymentMetadata['promocode'] = $promocodeParam;
        }

        try {
            $response = $sdk->create_payment(
                $orderId,
                $totalAmount,
                Option::get('currency'),
                '',
                '',
                config('app.url') . '/checkout',
                config('app.url') . '/api/cryptomus/webhook',
                config('app.url') . '/order-success',
            );

            if ($response) {
                Transaction::create([
                    'user_id' => null,
                    'guest_email' => $guestEmail,
                    'amount' => $totalAmount,
                    'currency' => Option::get('currency', 'USD'),
                    'payment_method' => 'cryptomus',
                    'status' => 'pending',
                    'metadata' => [
                        'order_id' => $orderId,
                        ...$paymentMetadata,
                    ],
                ]);

                return \App\Http\Responses\ApiResponse::success(['url' => $response]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
        } catch (\Exception $e) {
            Log::error('Guest Cryptomus payment creation failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail,
            ]);
            return response()->json(['success' => false, 'message' => 'Payment creation failed'], 500);
        }
    }

    /**
     * Create payment for balance top-up
     */
    public function createTopUpPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            Log::warning('Cryptomus top-up: Unauthorized attempt', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        $amount = round((float)$validated['amount'], 2);

        if ($amount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Минимальная сумма пополнения: 1 ' . Option::get('currency', 'USD')
            ], 422);
        }

        $orderId = 'topup_crypto_' . $user->id . '_' . time() . '_' . bin2hex(random_bytes(4));

        try {
            $sdk = new CryptomusSdk();

            $response = $sdk->create_payment(
                $orderId,
                $amount,
                Option::get('currency', 'USD'),
                '',
                '',
                config('app.url') . '/profile',
                config('app.url') . '/api/cryptomus/webhook',
                config('app.url') . '/profile?topup=success',
            );

            if ($response) {
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => Option::get('currency', 'USD'),
                    'payment_method' => 'cryptomus',
                    'status' => 'pending',
                    'metadata' => [
                        'order_id' => $orderId,
                        'payment_type' => 'topup',
                        'user_id' => $user->id,
                        'amount' => $amount,
                    ],
                ]);

                Log::info('Cryptomus top-up payment created', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'order_id' => $orderId,
                ]);

                return response()->json([
                    'success' => true,
                    'url' => $response,
                    'order_id' => $orderId,
                ]);
            }

            Log::error('Cryptomus top-up: Payment creation failed', [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать платеж. Попробуйте позже.'
            ], 422);

        } catch (\Exception $e) {
            Log::error('Cryptomus top-up: Exception', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при создании платежа'
            ], 500);
        }
    }

    /**
     * Handle Cryptomus webhook
     * Signature verification is handled by VerifyWebhookSignature middleware
     * According to Cryptomus docs: https://doc.cryptomus.com/merchant-api/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        Log::info('Cryptomus webhook received', [
            'body' => $request->all(),
        ]);

        $rawData = $request->getContent();
        $data = json_decode($rawData, true);

        if (!is_array($data)) {
            Log::error('Cryptomus webhook: Invalid JSON', ['raw' => substr($rawData, 0, 200)]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $orderId = $data['order_id'] ?? null;
        if (!$orderId) {
            Log::error('Cryptomus webhook: Missing order_id', ['data' => $data]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $sdk = new CryptomusSdk();
        $result = $sdk->read_result($data);

        if (!($result['status'] ?? false)) {
            Log::info('Cryptomus webhook: Payment not completed', [
                'order_id' => $orderId,
                'result' => $result,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $transaction = Transaction::whereRaw("JSON_EXTRACT(metadata, '$.order_id') = ?", [$orderId])->first();

        if (!$transaction || !$transaction->metadata) {
            Log::error('Cryptomus webhook: Transaction not found', [
                'order_id' => $orderId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $metadata = $transaction->metadata;

        if (!isset($metadata['payment_type'])) {
            Log::error('Cryptomus webhook: Missing payment_type', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // ВАЖНО: Проверяем дублирование покупок ДО обновления статуса транзакции
        // Для типов 'user' и 'guest' проверяем, что покупка еще не была создана
        if (in_array($metadata['payment_type'], ['user', 'guest'])) {
            $existingPurchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
            if ($existingPurchase) {
                Log::info('Cryptomus webhook: Purchase already exists for transaction (duplicate webhook)', [
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                    'purchase_id' => $existingPurchase->id,
                    'purchase_order_number' => $existingPurchase->order_number,
                ]);
                // Обновляем статус транзакции, если еще не обновлен
                if ($transaction->status !== 'completed') {
                    $transaction->status = 'completed';
                    $transaction->save();
                }
                return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);
            }
        }

        $transaction->status = 'completed';
        $transaction->save();

        Log::info('Cryptomus webhook: Processing payment', [
            'order_id' => $orderId,
            'transaction_id' => $transaction->id,
            'payment_type' => $metadata['payment_type'],
        ]);

        return match ($metadata['payment_type']) {
            'topup' => $this->handleTopUpWebhook($data, $metadata),
            'guest' => $this->handleGuestWebhook($data, $metadata),
            'user' => $this->handleUserPurchaseWebhook($data, $metadata, $transaction),
            default => $this->handleUnknownPaymentType($orderId),
        };
    }

    /**
     * Handle top-up webhook
     */
    private function handleTopUpWebhook(array $data, array $metadata): JsonResponse
    {
        $orderId = $data['order_id'] ?? null;
        $userId = $metadata['user_id'] ?? null;

        if (!$userId) {
            Log::error('Cryptomus webhook (TopUp): Missing user_id', ['order_id' => $orderId]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('Cryptomus webhook (TopUp): User not found', ['user_id' => $userId]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $amount = round((float)($metadata['amount'] ?? 0), 2);
        if ($amount <= 0) {
            Log::error('Cryptomus webhook (TopUp): Invalid amount', ['amount' => $amount]);
            return \App\Http\Responses\ApiResponse::success();
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
                    'cryptocurrency' => $data['payer_currency'] ?? 'unknown',
                    'network' => $data['network'] ?? 'unknown',
                    'webhook_received_at' => now()->toDateTimeString(),
                ]
            );

            if ($balanceTransaction) {
                Log::info('Cryptomus webhook (TopUp): Balance topped up', [
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

                return \App\Http\Responses\ApiResponse::success();
            }

            Log::info('Cryptomus webhook (TopUp): Duplicate transaction', ['order_id' => $orderId]);
            return \App\Http\Responses\ApiResponse::success();

        } catch (\InvalidArgumentException $e) {
            Log::error('Cryptomus webhook (TopUp): Validation error', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            return \App\Http\Responses\ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('Cryptomus webhook (TopUp): Processing failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }
    }

    /**
     * Handle user purchase webhook
     */
    private function handleUserPurchaseWebhook(array $data, array $metadata, Transaction $transaction): JsonResponse
    {
        $orderId = $data['order_id'] ?? null;
        $userId = $metadata['user_id'] ?? null;

        if (!$userId) {
            Log::error('Cryptomus webhook (User Purchase): Missing user_id', ['order_id' => $orderId]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('Cryptomus webhook (User Purchase): User not found', ['user_id' => $userId]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $productsData = $metadata['products_data'] ?? [];
        if (empty($productsData)) {
            Log::error('Cryptomus webhook (User Purchase): Invalid products data', ['order_id' => $orderId]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // ВАЖНО: Проверяем дублирование покупок
        $existingPurchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
        if ($existingPurchase) {
            Log::info('Cryptomus webhook (User Purchase): Purchase already exists (duplicate webhook)', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
                'purchase_id' => $existingPurchase->id,
                'user_id' => $userId,
            ]);
            return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);
        }

        $promocode = trim((string)($metadata['promocode'] ?? ''));

        try {
            // ВАЖНО: Подготавливаем данные с проверкой наличия и актуальной цены
            $preparedProductsData = [];
            foreach ($productsData as $item) {
                // Блокируем товар для проверки наличия и цены
                $product = \App\Models\ServiceAccount::lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    Log::warning('Cryptomus webhook (User Purchase): Product not found', ['product_id' => $item['product_id']]);
                    continue;
                }
                
                // Проверяем наличие товара
                $available = $product->getAvailableStock();
                if ($available < $item['quantity']) {
                    Log::error('Cryptomus webhook (User Purchase): Insufficient stock', [
                        'product_id' => $item['product_id'],
                        'requested' => $item['quantity'],
                        'available' => $available,
                    ]);
                    continue;
                }
                
                // Проверяем актуальную цену
                $currentPrice = $product->getCurrentPrice();
                $actualTotal = $currentPrice * $item['quantity'];
                
                if (abs($item['price'] - $currentPrice) > 0.01) {
                    Log::warning('Cryptomus webhook (User Purchase): Price changed', [
                        'product_id' => $item['product_id'],
                        'original_price' => $item['price'],
                        'current_price' => $currentPrice,
                        'original_total' => $item['total'],
                        'actual_total' => $actualTotal,
                    ]);
                }
                
                $preparedProductsData[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $currentPrice, // Используем актуальную цену
                    'total' => $actualTotal, // Пересчитываем с актуальной ценой
                ];
            }
            
            if (empty($preparedProductsData)) {
                Log::error('Cryptomus webhook (User Purchase): No valid products after validation', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);
                return \App\Http\Responses\ApiResponse::success();
            }

            $purchaseService = app(ProductPurchaseService::class);
            $purchases = $purchaseService->createMultiplePurchases(
                $preparedProductsData,
                $user->id,
                null,
                'crypto'
            );

            $totalAmount = array_sum(array_column($preparedProductsData, 'total'));

            $this->sendPurchaseNotifications($user, $totalAmount, $purchases);
            $this->recordPromocodeUsage($promocode, $user->id, $orderId);

            Log::info('Cryptomus webhook (User Purchase): Purchase completed', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'products_count' => count($purchases),
            ]);

            return \App\Http\Responses\ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('Cryptomus webhook (User Purchase): Processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'order_id' => $orderId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }
    }

    /**
     * Handle guest purchase webhook
     */
    private function handleGuestWebhook(array $data, array $metadata): JsonResponse
    {
        $orderId = $data['order_id'] ?? null;
        $guestEmail = trim((string)($metadata['guest_email'] ?? ''));

        if (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('Cryptomus webhook (Guest): Invalid email', [
                'email' => $guestEmail,
                'order_id' => $orderId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $productsData = $metadata['products_data'] ?? [];
        if (empty($productsData)) {
            Log::error('Cryptomus webhook (Guest): Invalid products data', ['order_id' => $orderId]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // ВАЖНО: Проверяем дублирование покупок для гостя
        $transaction = Transaction::whereRaw("JSON_EXTRACT(metadata, '$.order_id') = ?", [$orderId])->first();
        if ($transaction) {
            $existingPurchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
            if ($existingPurchase) {
                Log::info('Cryptomus webhook (Guest): Purchase already exists (duplicate webhook)', [
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                    'purchase_id' => $existingPurchase->id,
                    'guest_email' => $guestEmail,
                ]);
                return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);
            }
        }

        $promocode = trim((string)($metadata['promocode'] ?? ''));

        try {
            // ВАЖНО: Проверяем наличие товаров и актуальные цены перед созданием покупок
            $validatedProductsData = [];
            foreach ($productsData as $item) {
                $product = \App\Models\ServiceAccount::lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    Log::warning('Cryptomus webhook (Guest): Product not found', ['product_id' => $item['product_id']]);
                    continue;
                }
                
                // Проверяем наличие товара
                $available = $product->getAvailableStock();
                if ($available < $item['quantity']) {
                    Log::error('Cryptomus webhook (Guest): Insufficient stock', [
                        'product_id' => $item['product_id'],
                        'requested' => $item['quantity'],
                        'available' => $available,
                    ]);
                    continue;
                }
                
                // Проверяем актуальную цену
                $currentPrice = $product->getCurrentPrice();
                $actualTotal = $currentPrice * $item['quantity'];
                
                if (abs($item['price'] - $currentPrice) > 0.01) {
                    Log::warning('Cryptomus webhook (Guest): Price changed', [
                        'product_id' => $item['product_id'],
                        'original_price' => $item['price'],
                        'current_price' => $currentPrice,
                    ]);
                }
                
                $validatedProductsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $currentPrice,
                    'total' => $actualTotal,
                ];
            }
            
            if (empty($validatedProductsData)) {
                Log::error('Cryptomus webhook (Guest): No valid products after validation', [
                    'order_id' => $orderId,
                    'guest_email' => $guestEmail,
                ]);
                return response()->json(['success' => false, 'message' => 'No valid products'], 400);
            }
            
            GuestCartController::createGuestPurchases($guestEmail, $validatedProductsData, $promocode);

            $totalAmount = array_sum(array_column($validatedProductsData, 'total'));

            EmailService::sendToGuest(
                $guestEmail,
                'guest_purchase_confirmation',
                [
                    'products_count' => count($validatedProductsData),
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
                    'products' => count($validatedProductsData),
                    'amount' => number_format($totalAmount, 2),
                ]
            );

            $this->recordPromocodeUsage($promocode, null, $orderId);

            Log::info('Cryptomus webhook (Guest): Guest purchase completed', [
                'guest_email' => $guestEmail,
                'order_id' => $orderId,
                'products_count' => count($validatedProductsData),
            ]);

            return \App\Http\Responses\ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('Cryptomus webhook (Guest): Processing failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail,
                'order_id' => $orderId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }
    }

    /**
     * Handle unknown payment type
     */
    private function handleUnknownPaymentType(string $orderId): JsonResponse
    {
        Log::warning('Cryptomus webhook: Unknown payment type', ['order_id' => $orderId]);
        return \App\Http\Responses\ApiResponse::success();
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
                Log::warning('Cryptomus: Product not found', [
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
    private function sendPurchaseNotifications(User $user, float $totalAmount, array $purchases): void
    {
        EmailService::send('payment_confirmation', $user->id, [
            'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
        ]);

        if (!empty($purchases) && isset($purchases[0]) && $purchases[0]->order_number) {
            $notificationService = app(NotificationTemplateService::class);
            $notificationService->sendToUser($user, 'purchase', [
                'order_number' => $purchases[0]->order_number,
            ]);
        }

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
                // Проверяем, не был ли промокод уже использован для этого заказа
                $existingUsage = PromocodeUsage::where('order_id', (string)$orderId)->first();
                if ($existingUsage) {
                    Log::info('Cryptomus webhook: Promocode already used for this order', [
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'promocode' => $promocode,
                        'existing_usage_id' => $existingUsage->id,
                    ]);
                    return; // Промокод уже использован
                }
                
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
            Log::error('Failed to record promocode usage', [
                'promocode' => $promocode,
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
