<?php

namespace App\Console\Commands;

use App\Services\TelegramClientService;
use Illuminate\Console\Command;

class TelegramPollMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получить новые сообщения из Telegram и создать чаты в админ-панели';

    /**
     * Execute the console command.
     */
    public function handle(TelegramClientService $telegramService)
    {
        $enabled = \App\Models\Option::get('telegram_client_enabled', false);
        
        if (!$enabled) {
            $this->error('Telegram Client не включен в настройках');
            \Illuminate\Support\Facades\Log::debug('Telegram Client не включен, пропуск опроса сообщений');
            return 1;
        }

        $this->info('Получение новых сообщений из Telegram...');
        \Illuminate\Support\Facades\Log::debug('Запуск команды telegram:poll-messages');

        try {
            $messages = $telegramService->getNewMessages();
            
            if (empty($messages)) {
                $this->info('Новых сообщений нет');
                \Illuminate\Support\Facades\Log::debug('Новых сообщений из Telegram не найдено');
                return 0;
            }

            $this->info('Найдено новых сообщений: ' . count($messages));
            \Illuminate\Support\Facades\Log::info('Найдено новых сообщений из Telegram: ' . count($messages));

            $processedCount = 0;
            $errorCount = 0;

            foreach ($messages as $messageData) {
                try {
                    $chat = $telegramService->processIncomingMessage($messageData);
                    
                    if ($chat) {
                        $processedCount++;
                        $this->info("Обработано сообщение из чата #{$chat->id} (Telegram ID: {$messageData['chat_id']})");
                        \Illuminate\Support\Facades\Log::info("Обработано сообщение из Telegram", [
                            'chat_id' => $chat->id,
                            'telegram_chat_id' => $messageData['chat_id'],
                            'message_id' => $messageData['message_id'] ?? null,
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning("Не удалось обработать сообщение из Telegram", [
                            'telegram_chat_id' => $messageData['chat_id'] ?? null,
                            'message_id' => $messageData['message_id'] ?? null,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("Ошибка обработки сообщения: " . $e->getMessage());
                    \Illuminate\Support\Facades\Log::error('Ошибка обработки Telegram сообщения', [
                        'error' => $e->getMessage(),
                        'telegram_chat_id' => $messageData['chat_id'] ?? null,
                        'message_id' => $messageData['message_id'] ?? null,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->info("Обработка завершена. Обработано: {$processedCount}, ошибок: {$errorCount}");
            \Illuminate\Support\Facades\Log::info("Команда telegram:poll-messages завершена", [
                'processed' => $processedCount,
                'errors' => $errorCount,
                'total' => count($messages)
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Критическая ошибка: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Критическая ошибка получения Telegram сообщений', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
