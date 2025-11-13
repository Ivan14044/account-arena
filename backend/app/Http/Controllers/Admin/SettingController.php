<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $currency = Option::get('currency');

        return view('admin.settings.index', compact('currency'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules($request->form));
        echo '<pre>';
        print_r($validated);
        echo '</pre>';
        // exit;

        foreach ($validated as $key => $value) {
            if (!empty($value)) {
                Option::set($key, $value);
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('active_tab', $request->form)
            ->with('success', 'Settings saved successfully.');
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
            default => [
                'currency' => ['required', 'string'],
                'trial_days' => ['required', 'integer', 'between:0,30'],
                'discount_2' => ['required', 'integer'],
                'discount_3' => ['required', 'integer'],
            ],
        };
    }
}
