<?php

namespace App\Services;

use App\Models\{ServiceAccount, Purchase, Transaction, Option};
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
        ]);

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



