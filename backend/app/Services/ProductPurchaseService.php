<?php

namespace App\Services;

use App\Models\{ServiceAccount, Purchase, Transaction, Option, SupplierEarning, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    $usedCount = $product->used ?? 0;

    // Выбираем нужное количество неиспользованных аккаунтов
    $assignedAccounts = [];

    // Определяем локаль для суффикса
    $locale = app()->getLocale();
    if (!in_array($locale, ['ru', 'en', 'uk'])) {
        $locale = 'en';
    }

    // Получаем суффикс для текущей локали
    $suffixText = null;
    if ($product->account_suffix_enabled) {
        $suffixField = 'account_suffix_text_' . $locale;
        $suffixText = $product->$suffixField ?? null;
    }

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

    // Увеличиваем счетчик использованных
    $product->used = $usedCount + $quantity;
    $product->save();

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
        'account_data' => $assignedAccounts,
        'status' => 'completed',
    ]);

    // Логируем номер заказа для отслеживания
    Log::info('Purchase created', [
        'order_number' => $purchase->order_number,
        'user_id' => $userId,
        'guest_email' => $guestEmail,
        'product_id' => $product->id,
        'product_title' => $product->title,
        'quantity' => $quantity,
        'total' => $total,
    ]);

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
            $supplier = User::find($supplierId);
            if ($supplier && $supplier->is_supplier) {
                // Комиссия платформы в процентах (если null — по умолчанию 0)
                $supplierCommission = $supplier->supplier_commission !== null
                    ? (float)$supplier->supplier_commission
                    : 0.0;

                // За сколько процентов остаётся поставщику
                $supplierSharePercent = max(0, min(100, 100 - $supplierCommission));

                // Сумма, причитающаяся поставщику (округляем до 2 знаков)
                $supplierAmount = round($total * ($supplierSharePercent / 100.0), 2);

                if ($supplierAmount > 0) {
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



