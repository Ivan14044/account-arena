<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\AdminNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $currency = Option::get('currency');
        $notificationSettings = AdminNotificationSetting::getOrCreateForUser(auth()->id());

        return view('admin.settings.index', compact('currency', 'notificationSettings'));
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

        return redirect()->route('admin.settings.index')
            ->with('active_tab', $request->form)
            ->with('success', 'Настройки успешно сохранены.');
    }

    private function getRules($form)
    {
        return match ($form) {
            'header_menu' => [
                'header_menu' => ['required', 'array'],
            ],
            'footer_menu' => [
                'footer_menu' => ['required', 'array'],
            ],
            'cookie' => [
                'cookie_countries' => ['required', 'array'],
            ],
            'smtp' => [
                'from_address' => ['required', 'email'],
                'from_name' => ['required', 'string'],
                'host' => ['required'],
                'port' => ['required', 'integer'],
                'encryption' => ['required', 'string'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            'site_content' => [
                'currency' => ['required', 'string'],
                // Hero
                'hero_title_ru' => ['nullable', 'string'],
                'hero_title_en' => ['nullable', 'string'],
                'hero_title_uk' => ['nullable', 'string'],
                'hero_description_ru' => ['nullable', 'string'],
                'hero_description_en' => ['nullable', 'string'],
                'hero_description_uk' => ['nullable', 'string'],
                'hero_button_ru' => ['nullable', 'string'],
                'hero_button_en' => ['nullable', 'string'],
                'hero_button_uk' => ['nullable', 'string'],

                // About
                'about_title_ru' => ['nullable', 'string'],
                'about_title_en' => ['nullable', 'string'],
                'about_title_uk' => ['nullable', 'string'],
                'about_description_ru' => ['nullable', 'string'],
                'about_description_en' => ['nullable', 'string'],
                'about_description_uk' => ['nullable', 'string'],

                // Promote Title
                'promote_title_ru' => ['nullable', 'string'],
                'promote_title_en' => ['nullable', 'string'],
                'promote_title_uk' => ['nullable', 'string'],

                // Promote blocks (6 items: access, pricing, refund, activation, support, payment)
                'promote_access_title_ru' => ['nullable', 'string'],
                'promote_access_title_en' => ['nullable', 'string'],
                'promote_access_title_uk' => ['nullable', 'string'],
                'promote_access_description_ru' => ['nullable', 'string'],
                'promote_access_description_en' => ['nullable', 'string'],
                'promote_access_description_uk' => ['nullable', 'string'],

                'promote_pricing_title_ru' => ['nullable', 'string'],
                'promote_pricing_title_en' => ['nullable', 'string'],
                'promote_pricing_title_uk' => ['nullable', 'string'],
                'promote_pricing_description_ru' => ['nullable', 'string'],
                'promote_pricing_description_en' => ['nullable', 'string'],
                'promote_pricing_description_uk' => ['nullable', 'string'],

                'promote_refund_title_ru' => ['nullable', 'string'],
                'promote_refund_title_en' => ['nullable', 'string'],
                'promote_refund_title_uk' => ['nullable', 'string'],
                'promote_refund_description_ru' => ['nullable', 'string'],
                'promote_refund_description_en' => ['nullable', 'string'],
                'promote_refund_description_uk' => ['nullable', 'string'],

                'promote_activation_title_ru' => ['nullable', 'string'],
                'promote_activation_title_en' => ['nullable', 'string'],
                'promote_activation_title_uk' => ['nullable', 'string'],
                'promote_activation_description_ru' => ['nullable', 'string'],
                'promote_activation_description_en' => ['nullable', 'string'],
                'promote_activation_description_uk' => ['nullable', 'string'],

                'promote_support_title_ru' => ['nullable', 'string'],
                'promote_support_title_en' => ['nullable', 'string'],
                'promote_support_title_uk' => ['nullable', 'string'],
                'promote_support_description_ru' => ['nullable', 'string'],
                'promote_support_description_en' => ['nullable', 'string'],
                'promote_support_description_uk' => ['nullable', 'string'],

                'promote_payment_title_ru' => ['nullable', 'string'],
                'promote_payment_title_en' => ['nullable', 'string'],
                'promote_payment_title_uk' => ['nullable', 'string'],
                'promote_payment_description_ru' => ['nullable', 'string'],
                'promote_payment_description_en' => ['nullable', 'string'],
                'promote_payment_description_uk' => ['nullable', 'string'],

                // Steps
                'steps_title_ru' => ['nullable', 'string'],
                'steps_title_en' => ['nullable', 'string'],
                'steps_title_uk' => ['nullable', 'string'],
                'steps_description_ru' => ['nullable', 'string'],
                'steps_description_en' => ['nullable', 'string'],
                'steps_description_uk' => ['nullable', 'string'],
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
            default => [],
        };
    }
}
