<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\ServiceAccount;
use App\Services\ManualDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWaitingStockOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:waiting-stock-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically process orders waiting for stock when stock becomes available';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for orders waiting for stock...');

        // Находим заказы в статусе processing с флагом is_waiting_stock = true
        $waitingOrders = Purchase::where('status', Purchase::STATUS_PROCESSING)
            ->where('is_waiting_stock', true)
            ->with(['serviceAccount', 'user'])
            ->get();

        if ($waitingOrders->isEmpty()) {
            $this->info('No orders waiting for stock found.');
            return 0;
        }

        $this->info("Found {$waitingOrders->count()} order(s) waiting for stock.");

        $processedCount = 0;
        $stillWaitingCount = 0;

        $manualDeliveryService = app(ManualDeliveryService::class);

        foreach ($waitingOrders as $order) {
            try {
                // Проверяем наличие товара
                $product = ServiceAccount::find($order->service_account_id);
                
                if (!$product) {
                    $this->warn("Product not found for order #{$order->order_number}");
                    continue;
                }

                $availableStock = $product->getAvailableStock();
                
                if ($availableStock >= $order->quantity) {
                    // Товар появился в наличии - уведомляем менеджера
                    $this->info("Stock available for order #{$order->order_number}. Notifying manager...");
                    
                    // Уведомляем менеджера о том, что товар появился
                    \App\Services\NotifierService::send(
                        'stock_available_for_order',
                        "Товар появился в наличии для заказа #{$order->order_number}",
                        "Заказ #{$order->order_number} на товар \"{$product->title}\" ожидал появления товара. Товар теперь в наличии ({$availableStock} шт.), можно обработать заказ.",
                        'info'
                    );
                    
                    // Сбрасываем флаг ожидания (заказ останется в processing, менеджер обработает вручную)
                    $order->update([
                        'is_waiting_stock' => false,
                    ]);
                    
                    $processedCount++;
                } else {
                    // Товар все еще отсутствует
                    $stillWaitingCount++;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to process waiting stock order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to process order #{$order->order_number}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$processedCount} order(s). {$stillWaitingCount} order(s) still waiting for stock.");

        return 0;
    }
}
