<?php

namespace App\Services;

use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Log;

/**
 * Общая логика выдачи товаров в платёжных вебхуках (Mono/Cryptomus).
 *
 * Цикл перепроверки товаров был раскопирован в 4 обработчиках (user/guest ×
 * Mono/Cryptomus). Здесь — единая реализация.
 */
class PaymentFulfillmentService
{
    /**
     * Перепроверить позиции заказа перед выдачей: заблокировать каждый товар,
     * пропустить отсутствующие или без достаточного стока, пересчитать актуальную
     * цену. Возвращает список ['product' => ServiceAccount, 'quantity', 'price', 'total'].
     *
     * Форма с объектом 'product' принимается и user-, и guest-путём
     * (ProductPurchaseService::createMultiplePurchases читает product->id ?? product_id).
     *
     * @param array  $productsData Позиции из метаданных вебхука (ключи product_id, quantity, price, total).
     * @param string $logPrefix    Префикс для логов (например, "MonoBank Webhook (User Purchase)").
     */
    public function revalidateProducts(array $productsData, string $logPrefix): array
    {
        $prepared = [];

        foreach ($productsData as $item) {
            $productId = $item['product_id'] ?? null;
            $product = ServiceAccount::lockForUpdate()->find($productId);
            if (!$product) {
                Log::warning("{$logPrefix}: Product not found", ['product_id' => $productId]);
                continue;
            }

            $available = $product->getAvailableStock();
            if ($available < $item['quantity']) {
                Log::error("{$logPrefix}: Insufficient stock", [
                    'product_id' => $productId,
                    'requested' => $item['quantity'],
                    'available' => $available,
                ]);
                continue;
            }

            $currentPrice = $product->getCurrentPrice();
            $actualTotal = $currentPrice * $item['quantity'];

            if (abs(($item['price'] ?? 0) - $currentPrice) > 0.01) {
                Log::warning("{$logPrefix}: Price changed", [
                    'product_id' => $productId,
                    'original_price' => $item['price'] ?? null,
                    'current_price' => $currentPrice,
                    'original_total' => $item['total'] ?? null,
                    'actual_total' => $actualTotal,
                ]);
            }

            $prepared[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'price' => $currentPrice,
                'total' => $actualTotal,
            ];
        }

        return $prepared;
    }
}
