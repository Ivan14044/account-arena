<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Telegram
     */
    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            // Log incoming update for debugging
            Log::debug('Telegram webhook received', ['update' => $update]);

            $telegramBotService = new TelegramBotService();

            if (!$telegramBotService->isEnabled()) {
                Log::warning('TelegramBotService is not enabled, ignoring webhook');
                return response()->json(['ok' => true]);
            }

            // Handle /start command
            if (isset($update['message']['text'])) {
                $text = trim($update['message']['text']);
                
                // Check if it's /start command (can be "/start" or "/start@botname")
                if ($text === '/start' || strpos($text, '/start') === 0) {
                    $languageCode = $update['message']['from']['language_code'] ?? 'en';
                    $chatId = $update['message']['chat']['id'] ?? null;
                    
                    if ($chatId) {
                        $telegramBotService->sendGreetingMessage($chatId, $languageCode);
                        Log::info('Telegram /start command handled', [
                            'chat_id' => $chatId,
                            'language_code' => $languageCode
                        ]);
                    }
                    
                    // Don't create chat or message for /start command
                    return response()->json(['ok' => true]);
                }
            }

            // Process incoming message
            $supportChat = $telegramBotService->processIncomingMessage($update);

            if ($supportChat) {
                Log::info('Telegram message processed successfully', [
                    'chat_id' => $supportChat->id,
                    'telegram_chat_id' => $supportChat->telegram_chat_id
                ]);
            }

            // Always return OK to Telegram
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Still return OK to prevent Telegram from retrying
            return response()->json(['ok' => true]);
        }
    }
}



