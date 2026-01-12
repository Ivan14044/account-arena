<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Services\NotifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyOverdueManualOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:overdue-manual-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify managers about manual delivery orders that have been in processing status for more than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue manual delivery orders...');

        // Находим заказы в статусе processing старше 24 часов
        // И проверяем, что последнее напоминание было более 24 часов назад (или его не было)
        $overdueOrders = Purchase::where('status', Purchase::STATUS_PROCESSING)
            ->where('created_at', '<', now()->subHours(24))
            ->where(function($query) {
                $query->whereNull('last_reminder_at')
                      ->orWhere('last_reminder_at', '<', now()->subHours(24));
            })
            ->with('serviceAccount')
            ->get();

        if ($overdueOrders->isEmpty()) {
            $this->info('No overdue orders found that need notification.');
            return 0;
        }

        $this->info("Found {$overdueOrders->count()} overdue order(s) that need notification.");

        $notifiedCount = 0;

        foreach ($overdueOrders as $order) {
            try {
                $productTitle = $order->serviceAccount->title ?? 'Product';
                $hoursInProcessing = $order->created_at->diffInHours(now());
                $daysInProcessing = $order->created_at->diffInDays(now());
                
                // Определяем приоритет уведомления в зависимости от количества дней просрочки
                $priority = 'warning';
                if ($daysInProcessing >= 3) {
                    $priority = 'error'; // Критично для заказов старше 3 дней
                } elseif ($daysInProcessing >= 2) {
                    $priority = 'warning'; // Предупреждение для заказов старше 2 дней
                } else {
                    $priority = 'info'; // Информация для заказов старше 1 дня
                }
                
                // Формируем сообщение с учетом дней просрочки
                $message = "Заказ #{$order->order_number} на товар \"{$productTitle}\" находится в обработке более 24 часов ({$hoursInProcessing} ч., {$daysInProcessing} дн.). Требуется внимание менеджера.";
                if ($daysInProcessing >= 3) {
                    $message .= " КРИТИЧНО: заказ просрочен более 3 дней!";
                }

                NotifierService::send(
                    'overdue_manual_order',
                    "Просроченный заказ #{$order->order_number}",
                    $message,
                    $priority
                );

                // Обновляем дату последнего напоминания
                $order->update([
                    'last_reminder_at' => now(),
                ]);

                $notifiedCount++;

                $this->line("Notified about order #{$order->order_number} (priority: {$priority}, days: {$daysInProcessing})");
            } catch (\Throwable $e) {
                Log::error('Failed to notify about overdue order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to notify about order #{$order->order_number}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully notified about {$notifiedCount} order(s).");

        return 0;
    }
}
