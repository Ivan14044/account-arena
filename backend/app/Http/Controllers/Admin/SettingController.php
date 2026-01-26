<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\AdminNotificationSetting;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $currency = Option::get('currency');
        $notificationSettings = AdminNotificationSetting::getOrCreateForUser(auth()->id());
        
        $telegramSettings = [
            'enabled' => Option::get('telegram_client_enabled', false),
            'bot_token' => Option::get('telegram_bot_token', ''), // Автоматически расшифруется в Option::get
            'bot_username' => Option::get('telegram_bot_username', ''),
            'bot_id' => Option::get('telegram_bot_id', ''),
        ];

        $smtpPassword = Option::get('smtp_password', ''); // Автоматически расшифруется в Option::get

        return view('admin.settings.index', compact('currency', 'notificationSettings', 'telegramSettings', 'smtpPassword'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules($request->form));

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated) {
            foreach ($validated as $key => $value) {
                // ВАЖНО: Option::set теперь сам шифрует чувствительные поля.
                // Нам не нужно делать это здесь вручную.

                // Для checkbox полей нужно сохранять даже если они false
                if (in_array($key, ['support_chat_enabled', 'support_chat_greeting_enabled', 'smtp_verify_peer', 'dispute_auto_close_enabled'])) {
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
                    'low_stock_enabled' => $request->has('low_stock_enabled'),
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
     * Тестирование SMTP настроек перед сохранением
     * 
     * Позволяет проверить SMTP настройки перед их сохранением.
     * Использует временную конфигурацию из формы для проверки подключения.
     *
     * @param Request $request HTTP запрос с SMTP настройками
     * @return \Illuminate\Http\JsonResponse JSON ответ с результатом тестирования
     */
    public function testSmtp(Request $request)
    {
        $validated = $request->validate([
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        try {
            // Настраиваем временную SMTP конфигурацию для теста
            $encryption = !empty($validated['encryption']) ? $validated['encryption'] : null;
            
            Config::set('mail.mailers.test', [
                'transport' => 'smtp',
                'host' => $validated['host'],
                'port' => $validated['port'],
                'encryption' => $encryption,
                'username' => $validated['username'],
                'password' => $validated['password'],
                'timeout' => 10,
                'auth_mode' => null,
            ]);

            Config::set('mail.default', 'test');

            Config::set('mail.from', [
                'address' => $validated['from_address'],
                'name' => $validated['from_name'],
            ]);

            // Отправляем тестовое письмо
            $testEmail = $validated['from_address'];
            
            Mail::send('emails.test', [
                'host' => $validated['host'],
                'port' => $validated['port'],
                'encryption' => $encryption ?? 'Не используется',
                'from_address' => $validated['from_address'],
                'from_name' => $validated['from_name'],
                'timestamp' => now()->format('d.m.Y H:i:s'),
            ], function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Тест SMTP настроек - Account Arena');
            });

            Log::info('SMTP test successful', [
                'host' => $validated['host'],
                'port' => $validated['port'],
                'encryption' => $encryption,
                'from' => $validated['from_address'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Тестовое письмо успешно отправлено! Пожалуйста, проверьте ваш почтовый ящик ' . $testEmail . ' (включая папку Спам).',
            ]);
        } catch (\Exception $e) {
            Log::error('SMTP test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'host' => $validated['host'] ?? null,
                'port' => $validated['port'] ?? null,
            ]);

            $errorMessage = 'Ошибка при отправке тестового письма: ' . $e->getMessage();
            
            // Более понятные сообщения об ошибках для пользователя
            if (str_contains($e->getMessage(), 'Connection timed out')) {
                $errorMessage = 'Не удалось подключиться к SMTP серверу. Пожалуйста, проверьте правильность хоста и порта, а также убедитесь, что сервер доступен из вашей сети.';
            } elseif (str_contains($e->getMessage(), 'Authentication failed')) {
                $errorMessage = 'Ошибка аутентификации. Пожалуйста, проверьте правильность логина и пароля.';
            } elseif (str_contains($e->getMessage(), 'Could not connect to host')) {
                $errorMessage = 'Не удалось подключиться к хосту. Пожалуйста, проверьте правильность SMTP сервера.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 422);
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
                'smtp_verify_peer' => ['nullable', 'boolean'],
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
                'low_stock_enabled' => ['nullable', 'boolean'],
                'sound_enabled' => ['nullable', 'boolean'],
            ],
            'telegram' => [
                'telegram_client_enabled' => ['nullable', 'boolean'],
                'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            ],
            'pixel' => [
                'facebook_pixel_id' => ['nullable', 'string', 'max:255'],
            ],
            'disputes' => [
                'dispute_auto_close_enabled' => ['nullable', 'boolean'],
                'dispute_auto_close_hours' => ['required', 'integer', 'min:1', 'max:720'],
            ],
            default => [],
        };
    }
}
