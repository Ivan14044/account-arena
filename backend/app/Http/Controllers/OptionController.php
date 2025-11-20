<?php

namespace App\Http\Controllers;

use App\Models\Option;
use Illuminate\Support\Facades\Cache;

class OptionController extends Controller
{
    /**
     * Получить все опции с кешированием (кеш на 1 час)
     */
    public function index()
    {
        $options = Cache::remember('site_options', 3600, function () {
            return Option::pluck('value', 'name')->toArray();
        });
        
        return response()->json($options);
    }

    /**
     * Получить правила покупки с кешированием (кеш на 1 час)
     */
    public function getPurchaseRules()
    {
        $rules = Cache::remember('purchase_rules', 3600, function () {
            $enabled = Option::get('purchase_rules_enabled', false);
            
            // Приводим к boolean (на случай если в БД хранится как строка)
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
            
            // Если правила отключены, возвращаем пустой ответ
            if (!$enabled) {
                return [
                    'enabled' => false,
                    'rules' => [
                        'ru' => '',
                        'en' => '',
                        'uk' => '',
                    ],
                ];
            }

            // Получаем правила для всех языков
            return [
                'enabled' => true,
                'rules' => [
                    'ru' => Option::get('purchase_rules_ru', ''),
                    'en' => Option::get('purchase_rules_en', ''),
                    'uk' => Option::get('purchase_rules_uk', ''),
                ],
            ];
        });
        
        return response()->json($rules);
    }

    /**
     * Получить настройки чата поддержки с кешированием
     */
    public function getSupportChatSettings()
    {
        // Перехватываем вывод, чтобы MadelineProto не испортил JSON
        ob_start();
        
        try {
            // Получаем язык из запроса или используем текущую локаль
            $locale = request()->header('X-Locale') ?? request()->query('locale') ?? app()->getLocale();
            if (!in_array($locale, array_keys(config('langs')))) {
                $locale = app()->getLocale();
            }
            
            $cacheKey = 'support_chat_settings_' . $locale;
            $settings = Cache::remember($cacheKey, 3600, function () use ($locale) {
                $enabled = Option::get('support_chat_enabled', false);
                $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
                $telegramLink = Option::get('support_chat_telegram_link', 'https://t.me/support');
                $greetingEnabled = filter_var(Option::get('support_chat_greeting_enabled', false), FILTER_VALIDATE_BOOLEAN);
                
                // Получаем сообщения для нужного языка
                $greetingMessage = Option::get('support_chat_greeting_message_' . $locale, '');
                if (empty($greetingMessage)) {
                    $greetingMessage = Option::get('support_chat_greeting_message_ru', ''); // Fallback
                }
                
                return [
                    'enabled' => $enabled,
                    'telegram_link' => $telegramLink,
                    'greeting_enabled' => $greetingEnabled,
                    'greeting_message' => $greetingMessage,
                ];
            });
            
            // Очищаем весь перехваченный вывод (включая WARNING от MadelineProto)
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return response()->json($settings);
        } catch (\Exception $e) {
            // Очищаем вывод даже при ошибке
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            \Illuminate\Support\Facades\Log::error('Ошибка получения настроек чата', [
                'error' => $e->getMessage()
            ]);
            
            // Возвращаем настройки по умолчанию
            return response()->json([
                'enabled' => false,
                'telegram_link' => 'https://t.me/support',
                'greeting_enabled' => false,
                'greeting_message' => '',
            ]);
        }
    }
}
