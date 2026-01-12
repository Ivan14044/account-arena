<?php

namespace App\Services;

use App\Models\{ServiceAccount, Purchase, Transaction, Option, SupplierEarning, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ManualDeliveryService;

/**
 * Сервис для обработки покупок товаров
 * Устраняет дублирование кода между CartController и GuestCartController
 */
class ProductPurchaseService
{
    /**
     * Валидация и подготовка данных о товарах для покупки
     * 
     * @param array $productsRequest Массив товаров из запроса [['id' => 1, 'quantity' => 2], ...]
     * @return array ['success' => bool, 'data' => array|null, 'message' => string|null, 'total' => float]
     */
    public function prepareProductsData(array $productsRequest): array
    {
        $productsTotal = 0;
        $productsData = [];
        
        foreach ($productsRequest as $productItem) {
            $product = ServiceAccount::find($productItem['id']);
            if (!$product) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Product not found',
                    'total' => 0,
                ];
            }
            
            $quantity = $productItem['quantity'];
            $available = $product->getAvailableStock();
            
            // Проверяем доступность товара
            if ($available < $quantity) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => "Insufficient stock for {$product->title}. Available: {$available}, requested: {$quantity}",
                    'total' => 0,
                ];
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

        return [
            'success' => true,
            'data' => $productsData,
            'message' => null,
            'total' => $productsTotal,
        ];
    }

    /**
     * Создание покупки товара (выделение аккаунтов из товара)
     * 
     * @param ServiceAccount $product Товар
     * @param int $quantity Количество
     * @param float $price Цена за единицу
     * @param float $total Общая стоимость
     * @param int|null $userId ID пользователя (null для гостей)
     * @param string|null $guestEmail Email гостя (для гостевых покупок)
     * @param string $paymentMethod Метод оплаты
     * @return array ['transaction' => Transaction, 'purchase' => Purchase]
     */
public function createProductPurchase(
    ServiceAccount $product,
    int $quantity,
    float $price,
    float $total,
    ?int $userId = null,
    ?string $guestEmail = null,
    string $paymentMethod = 'balance'
): array {
    // ВАЖНО: Товар должен быть заблокирован (lockForUpdate) перед вызовом этого метода
    // Это предотвращает race condition при одновременной выдаче товара
    
    // Получаем аккаунты из accounts_data
    $accountsData = $product->accounts_data ?? [];
    
    // ВАЖНО: Проверяем, что accounts_data является массивом
    // Если это строка (JSON), пытаемся декодировать
    if (!is_array($accountsData)) {
        if (is_string($accountsData) && !empty($accountsData)) {
            $decoded = json_decode($accountsData, true);
            $accountsData = is_array($decoded) ? $decoded : [];
        } else {
            $accountsData = [];
        }
    }
    
    $usedCount = $product->used ?? 0;

    // Выбираем нужное количество неиспользованных аккаунтов
    $assignedAccounts = [];

    // Определяем локаль для суффикса
    $locale = app()->getLocale();
    if (!in_array($locale, ['ru', 'en', 'uk'])) {
        $locale = 'en';
    }

    // Определяем тип выдачи товара ДО выбора аккаунтов
    $deliveryType = $product->delivery_type ?? ServiceAccount::DELIVERY_AUTOMATIC;
    $requiresManualDelivery = ($deliveryType === ServiceAccount::DELIVERY_MANUAL);

    // Для ручной выдачи НЕ выбираем аккаунты - менеджер выберет их сам при обработке
    // Для автоматической выдачи - выбираем аккаунты сразу
    $assignedAccounts = [];
    
    if (!$requiresManualDelivery) {
        // Получаем суффикс для текущей локали (только для автоматической выдачи)
        $suffixText = null;
        if ($product->account_suffix_enabled) {
            $suffixField = 'account_suffix_text_' . $locale;
            $suffixText = $product->$suffixField ?? null;
        }

        // Выбираем аккаунты только для автоматической выдачи
        for ($i = 0; $i < $quantity; $i++) {
            if (isset($accountsData[$usedCount + $i])) {
                $account = $accountsData[$usedCount + $i];

                // Если включен суффикс и есть текст, добавляем его к аккаунту
                if ($suffixText) {
                    $account = $account . "\n" . $suffixText;
                }

                $assignedAccounts[] = $account;
            } else {
                // Если аккаунт не найден, выбрасываем исключение
                throw new \Exception("Insufficient accounts in product. Requested: {$quantity}, available: " . (count($accountsData) - $usedCount));
            }
        }

        // ВАЖНО: Проверяем, что аккаунты были успешно назначены
        if (empty($assignedAccounts)) {
            Log::error('ProductPurchaseService: No accounts assigned', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'used_count' => $usedCount,
                'accounts_data_count' => count($accountsData),
            ]);
            throw new \Exception("Failed to assign accounts. Product may be out of stock. Requested: {$quantity}, available: " . (count($accountsData) - $usedCount));
        }

        // ВАЖНО: Проверяем, что количество назначенных аккаунтов соответствует запрошенному
        if (count($assignedAccounts) !== $quantity) {
            Log::error('ProductPurchaseService: Mismatch in assigned accounts count', [
                'product_id' => $product->id,
                'requested_quantity' => $quantity,
                'assigned_count' => count($assignedAccounts),
            ]);
            throw new \Exception("Failed to assign all requested accounts. Requested: {$quantity}, assigned: " . count($assignedAccounts));
        }

        // Увеличиваем счетчик использованных только для автоматической выдачи
        $product->used = $usedCount + $quantity;
        $product->save();
    }

    // Создаем транзакцию для покупки
    $transaction = Transaction::create([
        'user_id' => $userId,
        'guest_email' => $guestEmail,
        'amount' => $total,
        'currency' => Option::get('currency'),
        'payment_method' => $paymentMethod,
        'service_account_id' => $product->id,
        'status' => 'completed',
    ]);

    // Определяем начальный статус и данные аккаунтов
    $initialStatus = $requiresManualDelivery 
        ? Purchase::STATUS_PROCESSING 
        : Purchase::STATUS_COMPLETED;
    
    // Для ручной выдачи account_data пока пустой (заполнится при обработке менеджером)
    $purchaseAccountData = $requiresManualDelivery 
        ? [] 
        : $assignedAccounts;

    // Создаем запись о покупке с уникальным номером заказа
    $purchase = Purchase::create([
        'order_number' => Purchase::generateOrderNumber(),
        'user_id' => $userId,
        'guest_email' => $guestEmail,
        'service_account_id' => $product->id,
        'transaction_id' => $transaction->id,
        'quantity' => $quantity,
        'price' => $price,
        'total_amount' => $total,
        'account_data' => $purchaseAccountData,
        'status' => $initialStatus,
    ]);

    // Записываем историю создания заказа
    try {
        \App\Models\PurchaseStatusHistory::createHistory(
            $purchase,
            $initialStatus,
            null, // Старого статуса нет, так как заказ только создан
            null, // Системное создание
            $requiresManualDelivery ? 'Заказ создан, требуется ручная обработка' : 'Заказ создан, автоматическая выдача'
        );
    } catch (\Throwable $e) {
        // Не ломаем создание заказа из-за ошибки записи истории
        Log::warning('Failed to create purchase status history', [
            'purchase_id' => $purchase->id,
            'error' => $e->getMessage(),
        ]);
    }

    // Логируем номер заказа для отслеживания
    Log::info('Purchase created', [
        'order_number' => $purchase->order_number,
        'user_id' => $userId,
        'guest_email' => $guestEmail,
        'product_id' => $product->id,
        'product_title' => $product->title,
        'quantity' => $quantity,
        'total' => $total,
        'delivery_type' => $deliveryType,
        'status' => $initialStatus,
    ]);

    // Уведомляем администратора и пользователя о новом заказе на ручную обработку
    if ($requiresManualDelivery) {
        try {
            $manualDeliveryService = app(ManualDeliveryService::class);
            // Уведомляем администратора
            $manualDeliveryService->notifyAdminAboutNewOrder($purchase);
            // Уведомляем пользователя о создании заказа
            $manualDeliveryService->notifyUserAboutOrderCreated($purchase);
        } catch (\Throwable $e) {
            // Не ломаем основную покупку из-за ошибки уведомления
            Log::error('Failed to notify about manual order', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /*
     * NEW: Создаём запись SupplierEarning вместо немедленного увеличения supplier_balance.
     * Логика:
     * - Если товар принадлежит поставщику (есть supplier_id), то вычисляем сумму для поставщика
     *   с учётом комиссии поставщика (supplier_commission %).
     * - available_at = now + supplier_hold_hours (или 6 часов по-умолчанию).
     * - status = 'held'.
     *
     * Это позволит позже переводить суммы в доступный баланс после окончания холда.
     */

    try {
        $supplierId = $product->supplier_id ?? null;
        if ($supplierId) {
            // ВАЖНО: Блокируем поставщика для предотвращения race condition при создании earnings
            $supplier = User::lockForUpdate()->find($supplierId);
            if ($supplier && $supplier->is_supplier) {
                // Комиссия платформы в процентах (если null — по умолчанию 0)
                $supplierCommission = $supplier->supplier_commission !== null
                    ? (float)$supplier->supplier_commission
                    : 0.0;

                // За сколько процентов остаётся поставщику
                $supplierSharePercent = max(0, min(100, 100 - $supplierCommission));

                // Сумма, причитающаяся поставщику (округляем до 2 знаков)
                $supplierAmount = round($total * ($supplierSharePercent / 100.0), 2);

                // ВАЖНО: Проверяем, что сумма положительная
                if ($supplierAmount <= 0) {
                    Log::warning('ProductPurchaseService: Supplier amount is zero or negative', [
                        'supplier_id' => $supplier->id,
                        'purchase_id' => $purchase->id ?? null,
                        'transaction_id' => $transaction->id ?? null,
                        'total' => $total,
                        'supplier_share_percent' => $supplierSharePercent,
                        'calculated_amount' => $supplierAmount,
                    ]);
                    return; // Пропускаем создание earning с нулевой или отрицательной суммой
                }

                // ВАЖНО: Проверяем, что для этой покупки еще не создан SupplierEarning
                // Это предотвращает дублирование при повторных вызовах
                $existingEarning = SupplierEarning::where('purchase_id', $purchase->id)
                    ->where('transaction_id', $transaction->id)
                    ->where('supplier_id', $supplier->id)
                    ->first();
                
                if ($existingEarning) {
                    Log::warning('ProductPurchaseService: SupplierEarning already exists for this purchase', [
                        'supplier_id' => $supplier->id,
                        'purchase_id' => $purchase->id,
                        'transaction_id' => $transaction->id,
                        'existing_earning_id' => $existingEarning->id,
                        'existing_amount' => $existingEarning->amount,
                        'existing_status' => $existingEarning->status,
                    ]);
                    return; // Пропускаем создание дубликата
                }

                $holdHours = (int) ($supplier->supplier_hold_hours ?? 6);
                $availableAt = now()->addHours($holdHours);

                SupplierEarning::create([
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $supplierAmount,
                    'status' => 'held',
                    'available_at' => $availableAt,
                ]);

                Log::info('Supplier earning created (held)', [
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $supplierAmount,
                    'available_at' => $availableAt->toDateTimeString(),
                ]);
            }
        }
    } catch (\Throwable $e) {
        // Не ломаем основную покупку, но логируем ошибку — потребуется ручная проверка
        Log::error('Failed to create supplier earning', [
            'error' => $e->getMessage(),
            'purchase_id' => $purchase->id ?? null,
            'transaction_id' => $transaction->id ?? null,
        ]);
    }

    return [
        'transaction' => $transaction,
        'purchase' => $purchase,
    ];
}


    /**
     * Массовое создание покупок товаров
     * 
     * @param array $productsData Массив подготовленных данных о товарах
     * @param int|null $userId ID пользователя (null для гостей)
     * @param string|null $guestEmail Email гостя (для гостевых покупок)
     * @param string $paymentMethod Метод оплаты
     * @return array Массив созданных покупок
     */
    public function createMultiplePurchases(
        array $productsData,
        ?int $userId = null,
        ?string $guestEmail = null,
        string $paymentMethod = 'balance'
    ): array {
        $purchases = [];
        
        DB::transaction(function () use ($productsData, $userId, $guestEmail, $paymentMethod, &$purchases) {
            foreach ($productsData as $item) {
                // ВАЖНО: Всегда блокируем товар для предотвращения race condition
                // Не полагаемся на состояние объекта, всегда делаем новый запрос с блокировкой
                $productId = $item['product']->id ?? $item['product_id'] ?? null;
                if (!$productId) {
                    Log::error('ProductPurchaseService: Missing product ID', [
                        'item' => $item,
                    ]);
                    throw new \Exception('Missing product ID in products data');
                }
                
                // Всегда блокируем товар через новый запрос
                $product = \App\Models\ServiceAccount::lockForUpdate()->find($productId);
                if (!$product) {
                    Log::error('ProductPurchaseService: Product not found during purchase creation', [
                        'product_id' => $productId,
                    ]);
                    throw new \Exception("Product not found: {$productId}");
                }
                
                // ВАЖНО: Проверяем наличие товара после блокировки (race condition protection)
                $available = $product->getAvailableStock();
                if ($available < $item['quantity']) {
                    Log::error('ProductPurchaseService: Insufficient stock after lock', [
                        'product_id' => $productId,
                        'requested_quantity' => $item['quantity'],
                        'available_stock' => $available,
                        'user_id' => $userId,
                        'guest_email' => $guestEmail,
                    ]);
                    throw new \Exception("Insufficient stock for product {$productId}. Available: {$available}, requested: {$item['quantity']}");
                }
                
                // Обновляем объект товара в массиве
                $item['product'] = $product;
                
                $result = $this->createProductPurchase(
                    $item['product'],
                    $item['quantity'],
                    $item['price'],
                    $item['total'],
                    $userId,
                    $guestEmail,
                    $paymentMethod
                );
                
                $purchases[] = $result['purchase'];
            }
        });

        return $purchases;
    }
}



