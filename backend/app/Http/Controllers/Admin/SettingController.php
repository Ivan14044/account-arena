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
        $botToken = Option::get('telegram_bot_token', '');
        if (!empty($botToken)) {
            try {
                $botToken = decrypt($botToken);
            } catch (\Exception $e) {
                // Not encrypted or wrong key
            }
        }

        $telegramSettings = [
            'enabled' => Option::get('telegram_client_enabled', false),
            'bot_token' => $botToken,
            'bot_username' => Option::get('telegram_bot_username', ''),
            'bot_id' => Option::get('telegram_bot_id', ''),
        ];

        // SMTP Password decryption for view
        $smtpPassword = Option::get('smtp_password', '');
        if (!empty($smtpPassword)) {
            try {
                $smtpPassword = decrypt($smtpPassword);
            } catch (\Exception $e) {
                // Not encrypted
            }
        }

        return view('admin.settings.index', compact('currency', 'notificationSettings', 'telegramSettings', 'smtpPassword'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules($request->form));

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated) {
            foreach ($validated as $key => $value) {
                // Шифруем чувствительные данные
                if (in_array($key, ['smtp_password', 'telegram_bot_token']) && !empty($value)) {
                    $value = encrypt($value);
                }

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
                        Option::set($greetingKey, $value ?? '');
                    }
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
                    'manual_delivery_enabled' => $request->has('manual_delivery_enabled'),
                    'sound_enabled' => $request->has('sound_enabled'),
                ]);
            }
        });

        // Очищаем кеш настроек чата поддержки
        if ($request->form === 'support_chat') {
            foreach (config('langs') as $locale => $flag) {
                Cache::forget('support_chat_settings_' . $locale);
            }
        }

        // Обработка настроек Telegram
        if ($request->form === 'telegram') {
            Option::set('telegram_client_enabled', $request->has('telegram_client_enabled') ? true : false);
            
            if ($request->filled('telegram_bot_token')) {
                // Токен уже зашифрован и сохранен в цикле выше
                $botToken = $request->telegram_bot_token;
                
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
                            Option::set('support_chat_telegram_link', "https://t.me/{$botUsername}");
                            
                            $webhookUrl = config('app.url') . '/api/telegram/webhook';
                            $telegramBotService = new TelegramBotService();
                            $webhookSet = $telegramBotService->setWebhook($webhookUrl);
                            
                            if (!$webhookSet) {
                                Log::warning('Failed to set Telegram webhook', ['webhook_url' => $webhookUrl]);
                            }
                        }
                    } else {
                        return redirect()->route('admin.settings.index')
                            ->with('active_tab', 'telegram')
                            ->withErrors(['telegram_bot_token' => 'Неверный токен бота.']);
                    }
                } catch (\Exception $e) {
                    Log::error('Telegram bot token validation error', ['error' => $e->getMessage()]);
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

    /**
     * Тестирование отправки письма через SMTP
     */
    public function testSmtp()
    {
        try {
            // ВАЖНО: Принудительно конфигурируем почту из сохраненных опций
            \App\Services\EmailService::configureMailFromOptions();
            
            $user = auth()->user();
            \Illuminate\Support\Facades\Mail::raw("Это тестовое письмо от Account Arena.\nЕсли вы получили это письмо, значит настройки SMTP работают корректно.\n\nТест выполнен: " . now()->format('d.m.Y H:i:s'), function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Тест SMTP - Account Arena');
            });

            return response()->json([
                'success' => true,
                'message' => 'Тестовое письмо успешно отправлено на ' . $user->email . '. Пожалуйста, проверьте ваш почтовый ящик (включая папку Спам).'
            ]);
        } catch (\Exception $e) {
            \Log::error('SMTP Test failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке тестового письма: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Получить настройки уведомлений для текущего администратора
     */
    public function getNotificationSettings()
    {
        $settings = AdminNotificationSetting::getOrCreateForUser(auth()->id());
        
        return response()->json([
            'manual_delivery_enabled' => $settings->manual_delivery_enabled ?? true,
            'sound_enabled' => $settings->sound_enabled ?? true,
            'dispute_created_enabled' => $settings->dispute_created_enabled ?? true,
            'support_chat_enabled' => $settings->support_chat_enabled ?? true,
        ]);
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
                'manual_delivery_enabled' => ['nullable', 'boolean'],
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
