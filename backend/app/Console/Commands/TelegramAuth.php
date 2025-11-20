<?php

namespace App\Console\Commands;

use App\Services\TelegramClientService;
use Illuminate\Console\Command;

class TelegramAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:auth {code?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Авторизация в Telegram через MadelineProto';

    /**
     * Execute the console command.
     */
    public function handle(TelegramClientService $telegramService)
    {
        $enabled = \App\Models\Option::get('telegram_client_enabled', false);
        
        if (!$enabled) {
            $this->error('Telegram Client не включен в настройках');
            return 1;
        }

        $code = $this->argument('code');

        if (!$code) {
            $this->info('Инициализация авторизации...');
            try {
                $telegramService->authorize();
                $this->info('Код отправлен в Telegram. Запустите команду с кодом:');
                $this->line('php artisan telegram:auth {код}');
                return 0;
            } catch (\Exception $e) {
                $this->error('Ошибка авторизации: ' . $e->getMessage());
                return 1;
            }
        }

        $this->info('Завершение авторизации с кодом...');
        
        if ($telegramService->completeAuth($code)) {
            $this->info('Авторизация успешно завершена!');
            return 0;
        } else {
            $this->error('Ошибка авторизации. Проверьте код и попробуйте снова.');
            return 1;
        }
    }
}
