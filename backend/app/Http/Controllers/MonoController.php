<?php

namespace App\Http\Controllers;

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
    /**
     * Webhook handler for MonoBank payment notifications
     * According to documentation: https://monobank.ua/api-docs/acquiring/methods/ia/post--api--merchant--invoice--create
     * 
     * The webhook body contains invoice status data identical to "Invoice Status" response:
     * - invoiceId: Invoice identifier
     * - status: Payment status (created, processing, success, failure, expired)
     * - amount: Payment amount in minimal units
     * - ccy: Currency code
     * - reference: Order reference (contains encoded payment metadata)
     * - modifiedDate: Last modification timestamp (use to handle out-of-order webhooks)
     * 
     * Note: Webhooks can arrive out of order. Use modifiedDate to determine the most current status.
     * Only process webhooks with status='success' for completed payments.
     */
    public function webhook(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        Log::info('MonoBank Webhook received', [
            'body' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Extract invoice data from webhook body (according to MonoBank documentation)
        $invoiceId = $request->input('invoiceId');
        $status = $request->input('status');
        $amount = $request->input('amount'); // Amount in minimal units (kopecks)
        $modifiedDateStr = $request->input('modifiedDate'); // ISO 8601 string
        
        // Convert ISO 8601 string to Unix timestamp (int) or null
        $modifiedDate = null;
        if ($modifiedDateStr) {
            try {
                $modifiedDate = Carbon::parse($modifiedDateStr)->timestamp;
            } catch (\Exception $e) {
                Log::warning('MonoBank Webhook: Failed to parse modifiedDate', [
                    'modifiedDate' => $modifiedDateStr,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Validate required fields
        if (!$invoiceId) {
            Log::error('MonoBank Webhook: Missing invoiceId', ['request' => $request->all()]);
            return \App\Http\Responses\ApiResponse::success(); // Return 200 to avoid retries
        }

        // Only process successful payments
        if ($status !== 'success') {
            Log::info('MonoBank Webhook: Payment not successful', [
                'invoiceId' => $invoiceId,
                'status' => $status,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // Find Transaction by invoiceId to get payment metadata
        $transaction = Transaction::whereRaw("JSON_EXTRACT(metadata, '$.invoice_id') = ?", [$invoiceId])
            ->first();
        
        if (!$transaction || !$transaction->metadata) {
            Log::error('MonoBank Webhook: Transaction not found by invoiceId', [
                'invoiceId' => $invoiceId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }
        
        $metadata = $transaction->metadata;
        
        // Reconstruct payment metadata from Transaction
        if (!isset($metadata['payment_type'])) {
            Log::error('MonoBank Webhook: Transaction found but missing payment_type', [
                'invoiceId' => $invoiceId,
                'transaction_id' => $transaction->id,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }
        
        $paymentMetadata = [
            'type' => $metadata['payment_type'],
        ];
        
        // Add all other metadata fields
        foreach ($metadata as $key => $value) {
            if ($key !== 'payment_type' && $key !== 'invoice_id') {
                $paymentMetadata[$key] = $value;
            }
        }
        
        Log::info('MonoBank Webhook: Found Transaction by invoiceId', [
            'invoiceId' => $invoiceId,
            'transaction_id' => $transaction->id,
            'payment_type' => $paymentMetadata['type'],
        ]);

        // ВАЖНО: Проверяем дублирование покупок ДО обновления статуса транзакции
        // Для типов 'user' и 'guest' проверяем, что покупка еще не была создана
        if (in_array($paymentMetadata['type'], ['user', 'guest'])) {
            $existingPurchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
            if ($existingPurchase) {
                Log::info('MonoBank Webhook: Purchase already exists for transaction (duplicate webhook)', [
                    'invoiceId' => $invoiceId,
                    'transaction_id' => $transaction->id,
                    'purchase_id' => $existingPurchase->id,
                    'purchase_order_number' => $existingPurchase->order_number,
                ]);
                // Обновляем статус транзакции, если еще не обновлен
                if ($transaction->status !== 'completed' && $status === 'success') {
                    $transaction->status = 'completed';
                    $transaction->save();
                }
                return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);
            }
        }

        // ВАЖНО: Мы НЕ обновляем статус транзакции здесь.
        // Мы сделаем это внутри обработчиков, чтобы гарантировать атомарность с созданием покупок.
        // Если обработчик упадет, транзакция останется в статусе pending для возможности ручного разбора.

        // Route to appropriate handler based on payment type
        return match ($paymentMetadata['type']) {
            'topup' => $this->handleTopUpWebhook($request, $invoiceId, $amount, $modifiedDate, $paymentMetadata, $transaction),
            'guest' => $this->handleGuestWebhook($request, $invoiceId, $amount, $modifiedDate, $paymentMetadata, $transaction),
            'user' => $this->handleUserPurchaseWebhook($request, $invoiceId, $amount, $modifiedDate, $paymentMetadata, $transaction),
            default => $this->handleUnknownPaymentType($invoiceId),
        };
    }

    /**
     * Handle unknown payment type
     */
    private function handleUnknownPaymentType(string $invoiceId): JsonResponse
    {
        Log::warning('MonoBank Webhook: Unknown payment type', [
            'invoiceId' => $invoiceId,
        ]);
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

        // Prepare payment metadata for Transaction
        $paymentMetadata = [
            'guest_email' => $guestEmail,
            'products_data' => $productsData,
            'promocode' => $promocodeParam,
        ];

        // Создаем invoice через Mono
        $invoice = MonoPaymentService::createInvoice(
            amount: $totalAmount,
            webhookUrl: config('app.url') . '/api/mono/webhook',
            options: [
                'successUrl' => config('app.url') . '/order-success',
                'failUrl' => config('app.url') . '/checkout?error=payment_failed',
            ]
        );

        if (isset($invoice['pageUrl']) && isset($invoice['invoiceId'])) {
            // Save Transaction with invoiceId for webhook lookup
            Transaction::create([
                'user_id' => null,
                'guest_email' => $guestEmail,
                'amount' => $totalAmount,
                'currency' => Option::get('currency', 'USD'),
                'payment_method' => 'monobank',
                'status' => 'pending',
                'metadata' => [
                    'invoice_id' => $invoice['invoiceId'],
                    'payment_type' => 'guest',
                    'guest_email' => $guestEmail,
                    'products_data' => $productsData,
                    'promocode' => $promocodeParam,
                ],
            ]);
            
            return \App\Http\Responses\ApiResponse::success(['url' => $invoice['pageUrl']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
    }

    /**
     * Создание платежа для авторизованного пользователя (покупка товаров)
     */
    public function createPayment(Request $request, PromocodeValidationService $promoService, \App\Services\ProductPurchaseService $purchaseService): JsonResponse
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
                    'message' => $promoData['message'] ?? 'Invalid promocode'
                ], 422);
            }
        }

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

        $totalAmount = max(round($totalAmount, 2), 0.01); // Минимальная сумма

        // Подготавливаем данные для webhook
        $productsDataForWebhook = collect($productsData)->map(function($item) {
            return [
                'product_id' => $item['product']->id,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total'],
            ];
        })->toArray();

        // Prepare payment metadata for Transaction
        $paymentMetadata = [
            'user_id' => $user->id,
            'products_data' => $productsDataForWebhook,
            'promocode' => $promocodeParam,
        ];

        // Создаем invoice через Mono
        $invoice = MonoPaymentService::createInvoice(
            amount: $totalAmount,
            webhookUrl: config('app.url') . '/api/mono/webhook',
            options: [
                'successUrl' => config('app.url') . '/order-success',
                'failUrl' => config('app.url') . '/checkout?error=payment_failed',
            ]
        );

        if (isset($invoice['pageUrl']) && isset($invoice['invoiceId'])) {
            // Save Transaction with invoiceId for webhook lookup
            Transaction::create([
                'user_id' => $user->id,
                'guest_email' => null,
                'amount' => $totalAmount,
                'currency' => Option::get('currency', 'USD'),
                'payment_method' => 'monobank',
                'status' => 'pending',
                'metadata' => [
                    'invoice_id' => $invoice['invoiceId'],
                    'payment_type' => 'user',
                    'user_id' => $user->id,
                    'products_data' => $productsDataForWebhook,
                    'promocode' => $promocodeParam,
                ],
            ]);
            
            return \App\Http\Responses\ApiResponse::success(['url' => $invoice['pageUrl']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
    }

    /**
     * Handle webhook for balance top-up via bank card
     * According to MonoBank API documentation, webhook contains invoice status data
     *
     * @param Request $request Webhook request
     * @param string $invoiceId Invoice identifier from webhook
     * @param int $amount Amount in minimal units (kopecks) from webhook
     * @param int|null $modifiedDate Last modification timestamp from webhook
     * @param array $metadata Payment metadata parsed from reference
     * @param Transaction $transaction Transaction model
     * @return JsonResponse
     */
    private function handleTopUpWebhook(Request $request, string $invoiceId, int $amount, ?int $modifiedDate, array $metadata, Transaction $transaction): JsonResponse
    {
        // Get user ID from metadata
        $userId = $metadata['user_id'] ?? null;
        if (!$userId) {
            Log::error('MonoBank Webhook (TopUp): Missing user_id in metadata', [
                'invoiceId' => $invoiceId,
                'metadata' => $metadata,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('MonoBank Webhook (TopUp): User not found', [
                'invoiceId' => $invoiceId,
                'user_id' => $userId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // Convert amount from minimal units (kopecks) to decimal
        $amountDecimal = round((float)($amount / 100), 2);
        
        if ($amountDecimal <= 0) {
            Log::error('MonoBank Webhook (TopUp): Invalid amount', [
                'invoiceId' => $invoiceId,
                'amount' => $amount,
                'amountDecimal' => $amountDecimal,
                'user_id' => $userId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        try {
            // ВАЖНО: Обновляем статус транзакции перед пополнением баланса
            $transaction->status = 'completed';
            $transaction->save();

            // Use BalanceService for safe balance top-up
            // BalanceService automatically checks for duplicates by invoice_id
            $balanceService = app(BalanceService::class);

            $balanceTransaction = $balanceService->topUp(
                user: $user,
                amount: $amountDecimal,
                type: BalanceService::TYPE_TOPUP_CARD,
                metadata: [
                    'invoice_id' => $invoiceId,
                    'payment_method' => 'monobank',
                    'payment_system' => 'monobank',
                    'webhook_received_at' => now()->toDateTimeString(),
                    'modified_date' => $modifiedDate,
                ]
            );

            // Check if operation was executed or if it's a duplicate
            if ($balanceTransaction) {
                Log::info('MonoBank Webhook (TopUp): Balance successfully topped up', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'amount' => $amountDecimal,
                    'invoiceId' => $invoiceId,
                    'balance_transaction_id' => $balanceTransaction->id,
                    'balance_after' => $balanceTransaction->balance_after,
                    'modified_date' => $modifiedDate,
                ]);

                // Send notification to administrator
                NotifierService::send(
                    'balance_topup',
                    'Баланс пополнен',
                    "Пользователь {$user->name} ({$user->email}) пополнил баланс на {$amountDecimal} " . Option::get('currency', 'USD') . " через Monobank"
                );

                return \App\Http\Responses\ApiResponse::success();
            }

            // If null returned, operation was already processed earlier
            Log::info('MonoBank Webhook (TopUp): Duplicate operation', [
                'user_id' => $user->id,
                'invoiceId' => $invoiceId,
                'amount' => $amountDecimal,
                'modified_date' => $modifiedDate,
            ]);

            return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);

        } catch (\InvalidArgumentException $e) {
            Log::error('MonoBank Webhook (TopUp): Validation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'invoiceId' => $invoiceId,
                'amount' => $amountDecimal,
            ]);
            return \App\Http\Responses\ApiResponse::success(); // Return 200 to avoid retries

        } catch (\Exception $e) {
            Log::error('MonoBank Webhook (TopUp): Critical error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'invoiceId' => $invoiceId,
                'amount' => $amountDecimal,
            ]);
            return \App\Http\Responses\ApiResponse::success(); // Return 200 to avoid retries
        }
    }

    /**
     * Handle webhook for authorized user product purchase
     * According to MonoBank API documentation, webhook contains invoice status data
     *
     * @param Request $request Webhook request
     * @param string $invoiceId Invoice identifier from webhook
     * @param int $amount Amount in minimal units (kopecks) from webhook
     * @param int|null $modifiedDate Last modification timestamp from webhook
     * @param array $metadata Payment metadata parsed from reference
     * @param Transaction $transaction Transaction model
     * @return JsonResponse
     */
    private function handleUserPurchaseWebhook(Request $request, string $invoiceId, int $amount, ?int $modifiedDate, array $metadata, Transaction $transaction): JsonResponse
    {
        // Get user ID from metadata
        $userId = $metadata['user_id'] ?? null;
        if (!$userId) {
            Log::error('MonoBank Webhook (User Purchase): Missing user_id in metadata', [
                'invoiceId' => $invoiceId,
                'metadata' => $metadata,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('MonoBank Webhook (User Purchase): User not found', [
                'invoiceId' => $invoiceId,
                'user_id' => $userId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // Get products data from metadata
        $productsData = $metadata['products_data'] ?? [];
        if (!is_array($productsData) || empty($productsData)) {
            Log::error('MonoBank Webhook (User Purchase): Invalid products data', [
                'invoiceId' => $invoiceId,
                'user_id' => $userId,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        $promocode = trim((string)($metadata['promocode'] ?? ''));

        try {
            // ВАЖНО: Получаем транзакцию для проверки дублирования
            if ($transaction) {
                $existingPurchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
                if ($existingPurchase) {
                    Log::info('MonoBank Webhook (User Purchase): Purchase already exists (duplicate webhook)', [
                        'invoiceId' => $invoiceId,
                        'transaction_id' => $transaction->id,
                        'purchase_id' => $existingPurchase->id,
                        'user_id' => $userId,
                    ]);
                    return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);
                }
            }

            $purchaseService = app(\App\Services\ProductPurchaseService::class);
            
            // ВАЖНО: Обновляем статус транзакции ПЕРЕД созданием покупок
            $transaction->status = 'completed';
            $transaction->save();

            // Подготавливаем данные о товарах для создания покупок
            $preparedProductsData = [];
            foreach ($productsData as $item) {
                // Блокируем товар для проверки наличия и цены
                $product = ServiceAccount::lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    Log::warning('Product not found in webhook', ['product_id' => $item['product_id']]);
                    continue;
                }
                
                // Проверяем наличие товара
                $available = $product->getAvailableStock();
                if ($available < $item['quantity']) {
                    Log::error('MonoBank Webhook (User Purchase): Insufficient stock', [
                        'product_id' => $item['product_id'],
                        'requested' => $item['quantity'],
                        'available' => $available,
                    ]);
                    continue;
                }
                
                // Проверяем актуальную цену (используем текущую цену товара)
                $currentPrice = $product->getCurrentPrice();
                $actualTotal = $currentPrice * $item['quantity'];
                
                // Логируем, если цена изменилась
                if (abs($item['price'] - $currentPrice) > 0.01) {
                    Log::warning('MonoBank Webhook (User Purchase): Price changed', [
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
                Log::error('MonoBank Webhook (User Purchase): No valid products after validation', [
                    'invoiceId' => $invoiceId,
                    'user_id' => $userId,
                ]);
                return response()->json(['success' => false, 'message' => 'No valid products'], 400);
            }
            
            // Создаем покупки для авторизованного пользователя
            // ВАЖНО: Передаем промокод и invoiceId для атомарной записи использования
            $purchases = $purchaseService->createMultiplePurchases(
                $preparedProductsData,
                $user->id,
                null, // guest_email = null для авторизованных
                'credit_card',
                $promocode,
                $invoiceId
            );

            // Отправляем email уведомление пользователю
            $totalAmount = array_sum(array_column($productsData, 'total'));
            try {
                EmailService::send('payment_confirmation', $user->id, [
                    'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
                ]);
            } catch (\Exception $e) {
                Log::warning('MonoBank Webhook (User Purchase): Failed to send payment confirmation email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Отправляем уведомление пользователю о покупке
            if (!empty($purchases) && isset($purchases[0]) && $purchases[0]->order_number) {
                $notificationService = app(NotificationTemplateService::class);
                $notificationService->sendToUser($user, 'purchase', [
                    'order_number' => $purchases[0]->order_number,
                ]);
            }

            // Уведомление админу о новой покупке
            NotifierService::sendFromTemplate(
                'product_purchase',
                'admin_product_purchase',
                [
                    'method' => 'Monobank',
                    'email' => $user->email,
                    'name' => $user->name,
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ]
            );

            Log::info('MonoBank Webhook (User Purchase): Purchase completed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'invoiceId' => $invoiceId,
                'products_count' => count($productsData),
                'modified_date' => $modifiedDate,
            ]);

            return \App\Http\Responses\ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('MonoBank Webhook (User Purchase): Processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'invoiceId' => $invoiceId,
                'trace' => $e->getTraceAsString(),
            ]);
            return \App\Http\Responses\ApiResponse::success(); // Return 200 to avoid retries
        }
    }

    /**
     * Handle webhook for guest payment
     * According to MonoBank API documentation, webhook contains invoice status data
     *
     * @param Request $request Webhook request
     * @param string $invoiceId Invoice identifier from webhook
     * @param int $amount Amount in minimal units (kopecks) from webhook
     * @param int|null $modifiedDate Last modification timestamp from webhook
     * @param array $metadata Payment metadata parsed from reference
     * @param Transaction $transaction Transaction model
     * @return JsonResponse
     */
    private function handleGuestWebhook(Request $request, string $invoiceId, int $amount, ?int $modifiedDate, array $metadata, Transaction $transaction): JsonResponse
    {
        // Get guest email from metadata
        $guestEmail = trim((string)($metadata['guest_email'] ?? ''));
        if (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('MonoBank Webhook (Guest): Invalid guest email', [
                'invoiceId' => $invoiceId,
                'metadata' => $metadata,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // Get products data from metadata
        $productsData = $metadata['products_data'] ?? [];
        if (!is_array($productsData) || empty($productsData)) {
            Log::error('MonoBank Webhook (Guest): Invalid products data', [
                'invoiceId' => $invoiceId,
                'guest_email' => $guestEmail,
            ]);
            return \App\Http\Responses\ApiResponse::success();
        }

        // ВАЖНО: Проверяем дублирование покупок для гостя
        if ($transaction) {
            $existingPurchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
            if ($existingPurchase) {
                Log::info('MonoBank Webhook (Guest): Purchase already exists (duplicate webhook)', [
                    'invoiceId' => $invoiceId,
                    'transaction_id' => $transaction->id,
                    'purchase_id' => $existingPurchase->id,
                    'guest_email' => $guestEmail,
                ]);
                return \App\Http\Responses\ApiResponse::success(['message' => 'Already processed']);
            }
        }

        $promocode = trim((string)($metadata['promocode'] ?? ''));

        try {
            // ВАЖНО: Обновляем статус транзакции ПЕРЕД созданием покупок
            $transaction->status = 'completed';
            $transaction->save();

            // ВАЖНО: Проверяем наличие товаров и актуальные цены перед созданием покупок
            $validatedProductsData = [];
            foreach ($productsData as $item) {
                $product = ServiceAccount::lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    Log::warning('MonoBank Webhook (Guest): Product not found', ['product_id' => $item['product_id']]);
                    continue;
                }
                
                // Проверяем наличие товара
                $available = $product->getAvailableStock();
                if ($available < $item['quantity']) {
                    Log::error('MonoBank Webhook (Guest): Insufficient stock', [
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
                    Log::warning('MonoBank Webhook (Guest): Price changed', [
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
                Log::error('MonoBank Webhook (Guest): No valid products after validation', [
                    'invoiceId' => $invoiceId,
                    'guest_email' => $guestEmail,
                ]);
                return response()->json(['success' => false, 'message' => 'No valid products'], 400);
            }
            
            // Создаем покупки для гостя
            // ВАЖНО: Передаем промокод и invoiceId для атомарной записи использования
            GuestCartController::createGuestPurchases($guestEmail, $validatedProductsData, $promocode, $invoiceId);

            // Отправляем email уведомление гостю с информацией о покупке
            $totalAmount = array_sum(array_column($validatedProductsData, 'total'));
            try {
                EmailService::sendToGuest(
                    $guestEmail,
                    'guest_purchase_confirmation',
                    [
                        'products_count' => count($validatedProductsData),
                        'total_amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency')),
                        'guest_email' => $guestEmail,
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('MonoBank Webhook (Guest): Failed to send purchase confirmation email', [
                    'guest_email' => $guestEmail,
                    'error' => $e->getMessage(),
                ]);
            }

            // Уведомление админу о новой гостевой покупке
            NotifierService::sendFromTemplate(
                'guest_product_purchase',
                'admin_product_purchase',
                [
                    'method' => 'Monobank',
                    'email' => $guestEmail,
                    'name' => 'Гость',
                    'products' => count($validatedProductsData),
                    'amount' => number_format($totalAmount, 2),
                ]
            );

            Log::info('MonoBank Webhook (Guest): Guest purchase completed', [
                'guest_email' => $guestEmail,
                'invoiceId' => $invoiceId,
                'products_count' => count($validatedProductsData),
                'modified_date' => $modifiedDate,
            ]);

            return \App\Http\Responses\ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('MonoBank Webhook (Guest): Processing failed', [
                'error' => $e->getMessage(),
                'guest_email' => $guestEmail,
                'invoiceId' => $invoiceId,
                'trace' => $e->getTraceAsString(),
            ]);
            return \App\Http\Responses\ApiResponse::success(); // Return 200 to avoid retries
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
                webhookUrl: config('app.url') . '/api/mono/webhook',
                options: [
                    'successUrl' => config('app.url') . '/profile?topup=success',
                    'failUrl' => config('app.url') . '/profile?topup=failed',
                ]
            );

            // Проверяем, что invoice создан успешно
            if (isset($invoice['pageUrl']) && isset($invoice['invoiceId'])) {
                // Save Transaction with invoiceId for webhook lookup
                Transaction::create([
                    'user_id' => $user->id,
                    'guest_email' => null,
                    'amount' => $amount,
                    'currency' => Option::get('currency', 'USD'),
                    'payment_method' => 'monobank',
                    'status' => 'pending',
                    'metadata' => [
                        'invoice_id' => $invoice['invoiceId'],
                        'payment_type' => 'topup',
                        'user_id' => $user->id,
                        'amount' => $amount,
                    ],
                ]);
                
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
}
