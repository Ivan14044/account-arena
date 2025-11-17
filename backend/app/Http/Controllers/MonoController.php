<?php

namespace App\Http\Controllers;

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
    // Services are no longer supported - only products are available
    // This method has been removed

    public function webhook(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        Log::info('Mono Webhook received', $request->all());

        if ($request->status !== 'success') {
            return \App\Http\Responses\ApiResponse::success();
        }

        // Проверяем, это пополнение баланса?
        if ($request->has('is_topup') && $request->is_topup == '1') {
            return $this->handleTopUpWebhook($request);
        }

        // Проверяем, это гостевой платеж?
        if ($request->has('is_guest') && $request->is_guest == '1') {
            return $this->handleGuestWebhook($request);
        }

        // Проверяем, это покупка товаров для авторизованного пользователя?
        if ($request->has('user_id') && $request->has('products_data')) {
            return $this->handleUserPurchaseWebhook($request);
        }

        // Services are no longer supported - only products are available
        // If this webhook is for services, return success (legacy support)
        if ($request->has('service_ids')) {
            Log::warning('Mono webhook received for services, but services are no longer supported', $request->all());
            return \App\Http\Responses\ApiResponse::success();
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
        if ($personalDiscountPercent > 0) {
            $totalAmount = $totalAmount - ($totalAmount * $personalDiscountPercent / 100);
        }

        // Применяем скидку по промокоду если есть (применяется после персональной скидки)
        if ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $discountPercent = floatval($promoData['discount_percent'] ?? 0);
            $totalAmount = $totalAmount - ($totalAmount * $discountPercent / 100);
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

        // Создаем invoice через Mono
        $invoice = MonoPaymentService::createInvoice(
            amount: $totalAmount,
            redirectUrl: config('app.url') . '/checkout?success=true',
            webhookUrl: config('app.url') . '/api/mono/webhook?' . http_build_query([
                'user_id' => $user->id,
                'products_data' => base64_encode(json_encode($productsDataForWebhook)),
                'promocode' => $promocodeParam,
            ]),
            walletId: 'wallet_user_' . $user->id . '_' . time(),
        );

        if (isset($invoice['pageUrl'])) {
            return \App\Http\Responses\ApiResponse::success(['url' => $invoice['pageUrl']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
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
     * Обработка webhook для покупки товаров авторизованным пользователем
     */
    private function handleUserPurchaseWebhook(Request $request): JsonResponse
    {
        $userId = $request->user_id;
        if (!$userId) {
            Log::error('Invalid user_id in user purchase webhook');
            return response()->json(['success' => false, 'message' => 'Invalid user_id'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('User not found in webhook', ['user_id' => $userId]);
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $productsDataEncoded = $request->products_data ?? '';
        $productsData = json_decode(base64_decode($productsDataEncoded), true);
        
        if (!is_array($productsData) || empty($productsData)) {
            Log::error('Invalid products data in user purchase webhook');
            return response()->json(['success' => false, 'message' => 'Invalid products data'], 400);
        }

        $promocode = trim((string)($request->promocode ?? ''));

        try {
            $purchaseService = app(\App\Services\ProductPurchaseService::class);
            
            // Подготавливаем данные о товарах для создания покупок
            $preparedProductsData = [];
            foreach ($productsData as $item) {
                $product = ServiceAccount::find($item['product_id']);
                if (!$product) {
                    Log::warning('Product not found in webhook', ['product_id' => $item['product_id']]);
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
                Log::error('No valid products found in webhook');
                return response()->json(['success' => false, 'message' => 'No valid products'], 400);
            }
            
            // Создаем покупки для авторизованного пользователя
            $purchases = $purchaseService->createMultiplePurchases(
                $preparedProductsData,
                $user->id,
                null, // guest_email = null для авторизованных
                'credit_card'
            );

            // Отправляем email уведомление пользователю
            $totalAmount = array_sum(array_column($productsData, 'total'));
            EmailService::send('payment_confirmation', $user->id, [
                'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
            ]);

            // Уведомление админу о новой покупке
            NotifierService::send(
                'product_purchase',
                __('notifier.new_product_purchase_title', ['method' => 'Monobank']),
                __('notifier.new_product_purchase_message', [
                    'email' => $user->email,
                    'name' => $user->name,
                    'products' => count($productsData),
                    'amount' => number_format($totalAmount, 2),
                ])
            );

            Log::info('User purchase completed via Monobank', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'invoice_id' => $request->invoiceId ?? 'unknown',
                'products_count' => count($productsData),
            ]);

            // Записываем использование промокода если есть
            if ($promocode !== '') {
                DB::transaction(function () use ($promocode, $user, $request) {
                    $promo = Promocode::where('code', $promocode)->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                            'promocode_id' => $promo->id,
                            'user_id' => $user->id,
                            'order_id' => (string)($request->invoiceId ?? ''),
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
            Log::error('User purchase webhook processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Processing failed'], 500);
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

    // Services are no longer supported - this method has been removed
}
