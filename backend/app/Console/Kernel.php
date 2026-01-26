<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Subscription commands removed - commands do not exist
        // $schedule->command('subscriptions:cancel-expired')->everyMinute();
        // $schedule->command('subscriptions:notify-expiring')->hourly();
        // $schedule->command('subscriptions:check-payments')->twiceDaily(10, 16);

        // Пересчет рейтингов поставщиков (каждый день в 3:00)
        $schedule->command('suppliers:recalculate-ratings')->dailyAt('03:00');

        // Перевод средств поставщиков из холда в доступный баланс (каждые 5 минут)
        $schedule->command('suppliers:release-earnings')->everyFiveMinutes();

        // Напоминания менеджерам о просроченных заказах на ручную обработку (каждый час)
        $schedule->command('notify:overdue-manual-orders')->hourly();

        // Автоматическая обработка заказов, ожидающих появления товара (каждые 30 минут)
        $schedule->command('process:waiting-stock-orders')->everyThirtyMinutes();

        // Автоматическое закрытие диспутов при отсутствии активности (каждый час)
        $schedule->command('disputes:auto-close')->hourly();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
