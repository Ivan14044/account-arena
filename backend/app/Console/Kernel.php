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
        $schedule->command('subscriptions:cancel-expired')->everyMinute();
        $schedule->command('subscriptions:notify-expiring')->hourly();
        $schedule->command('subscriptions:check-payments')->twiceDaily(10, 16);
        
        // Пересчет рейтингов поставщиков (каждый день в 3:00)
        $schedule->command('suppliers:recalculate-ratings')->dailyAt('03:00');
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
