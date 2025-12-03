<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\AdminNotificationSetting;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $currency = Option::get('currency');
        $notificationSettings = AdminNotificationSetting::getOrCreateForUser(auth()->id());
        
        // Настройки Telegram
        $telegramSettings = [
            'enabled' => Option::get('telegram_client_enabled', false),
            'bot_token' => Option::get('telegram_bot_token', ''),
            'bot_username' => Option::get('telegram_bot_username', ''),
            'bot_id' => Option::get('telegram_bot_id', ''),
        ];

        return view('admin.settings.index', compact('currency', 'notificationSettings', 'telegramSettings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules($request->form));

        foreach ($validated as $key => $value) {
            // Для checkbox полей нужно сохранять даже если они false
            if (in_array($key, ['support_chat_enabled', 'support_chat_greeting_enabled'])) {
                Option::set($key, $request->has($key) ? true : false);
            } elseif (!empty($value) || $value === '0' || $value === 0) {
                Option::set($key, $value);
            }
        }

        // Special handling for SMTP encryption: empty string should be saved as empty string (not skipped)
        if ($request->form === 'smtp' && $request->has('smtp_encryption')) {
            $encryption = $request->input('smtp_encryption');
            Option::set('smtp_encryption', $encryption ?? '');
        }


        // Сохраняем сообщения для разных языков (приветствие)
        if ($request->form === 'support_chat') {
            foreach (config('langs') as $locale => $flag) {
                $greetingKey = 'support_chat_greeting_message_' . $locale;
                if ($request->has($greetingKey)) {
                    $value = $request->input($greetingKey);
                    // Convert null to empty string to avoid database constraint violation
                    Option::set($greetingKey, $value ?? '');
                }
            }
        }

        // Очищаем кеш настроек чата поддержки при изменении настроек чата
        if ($request->form === 'support_chat') {
            foreach (config('langs') as $locale => $flag) {
                Cache::forget('support_chat_settings_' . $locale);
            }
        }

        // Обработка настроек уведомлений
        if ($request->form === 'notification_settings') {
            $notificationSettings = AdminNotificationSetting::getOrCreateForUser(auth()->id());
            $notificationSettings->update([
                'registration_enabled' => $request->has('registration_enabled'),
                'product_purchase_enabled' => $request->has('product_purchase_enabled'),
                'dispute_created_enabled' => $request->has('dispute_created_enabled'),
                'payment_enabled' => $request->has('payment_enabled'),
                'topup_enabled' => $request->has('topup_enabled'),
                'support_chat_enabled' => $request->has('support_chat_enabled'),
                'sound_enabled' => $request->has('sound_enabled'),
            ]);
        }

        // Обработка настроек Telegram
        if ($request->form === 'telegram') {
            Option::set('telegram_client_enabled', $request->has('telegram_client_enabled') ? true : false);
            
            if ($request->filled('telegram_bot_token')) {
                $botToken = $request->telegram_bot_token;
                Option::set('telegram_bot_token', $botToken);
                
                // Validate bot token by calling /getMe API
                try {
                    $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getMe");
                    
                    if ($response->successful() && $response->json('ok')) {
                        $botData = $response->json('result');
                        $botUsername = $botData['username'] ?? null;
                        $botId = $botData['id'] ?? null;
                        
                        if ($botUsername) {
                            Option::set('telegram_bot_username', $botUsername);
                            Option::set('telegram_bot_id', $botId);
                            
                            // Set support_chat_telegram_link with correct bot link
                            Option::set('support_chat_telegram_link', "https://t.me/{$botUsername}");
                            
                            // Set webhook for receiving messages
                            $webhookUrl = config('app.url') . '/api/telegram/webhook';
                            $telegramBotService = new TelegramBotService();
                            $webhookSet = $telegramBotService->setWebhook($webhookUrl);
                            
                            if (!$webhookSet) {
                                Log::warning('Failed to set Telegram webhook', [
                                    'webhook_url' => $webhookUrl
                                ]);
                            }
                        }
                    } else {
                        return redirect()->route('admin.settings.index')
                            ->with('active_tab', 'telegram')
                            ->withErrors(['telegram_bot_token' => 'Неверный токен бота. Проверьте правильность токена.']);
                    }
                } catch (\Exception $e) {
                    Log::error('Telegram bot token validation error', [
                        'error' => $e->getMessage()
                    ]);
                    
                    return redirect()->route('admin.settings.index')
                        ->with('active_tab', 'telegram')
                        ->withErrors(['telegram_bot_token' => 'Ошибка при проверке токена бота: ' . $e->getMessage()]);
                }
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('active_tab', $request->form)
            ->with('success', 'Настройки успешно сохранены.');
    }

    private function getRules($form)
    {
        return match ($form) {
            'cookie' => [
                'cookie_countries' => ['required', 'array'],
            ],
            'smtp' => [
                'smtp_from_address' => ['required', 'email'],
                'smtp_from_name' => ['required', 'string'],
                'smtp_host' => ['required', 'string'],
                'smtp_port' => ['required', 'integer'],
                'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl'], // Can be empty for no encryption
                'smtp_username' => ['required', 'string'],
                'smtp_password' => ['required', 'string'],
            ],
            'support_chat' => [
                'support_chat_enabled' => ['nullable', 'boolean'],
                'support_chat_telegram_link' => ['nullable', 'url', 'max:255'],
                'support_chat_greeting_enabled' => ['nullable', 'boolean'],
            ],
            'notification_settings' => [
                'registration_enabled' => ['nullable', 'boolean'],
                'product_purchase_enabled' => ['nullable', 'boolean'],
                'dispute_created_enabled' => ['nullable', 'boolean'],
                'payment_enabled' => ['nullable', 'boolean'],
                'topup_enabled' => ['nullable', 'boolean'],
                'support_chat_enabled' => ['nullable', 'boolean'],
                'sound_enabled' => ['nullable', 'boolean'],
            ],
            'telegram' => [
                'telegram_client_enabled' => ['nullable', 'boolean'],
                'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            ],
            default => [],
        };
    }
}
