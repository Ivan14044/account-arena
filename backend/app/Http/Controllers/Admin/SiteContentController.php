<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    public function index()
    {
        $currency = Option::get('currency');
        
        return view('admin.site-content.index', compact('currency'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules($request->form));

        foreach ($validated as $key => $value) {
            if (!empty($value) || $value === '0' || $value === 0) {
                Option::set($key, $value);
            }
        }

        // Special handling for menu forms
        if ($request->form === 'header_menu' || $request->form === 'footer_menu') {
            $menuData = $request->input($request->form, []);
            Option::set($request->form, json_encode($menuData));
        }

        return redirect()->route('admin.site-content.index')
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

                // Become Supplier - Welcome Banner
                'become_supplier_welcome_headline_ru' => ['nullable', 'string'],
                'become_supplier_welcome_headline_en' => ['nullable', 'string'],
                'become_supplier_welcome_headline_uk' => ['nullable', 'string'],
                'become_supplier_welcome_subtitle_ru' => ['nullable', 'string'],
                'become_supplier_welcome_subtitle_en' => ['nullable', 'string'],
                'become_supplier_welcome_subtitle_uk' => ['nullable', 'string'],
                'become_supplier_welcome_cta_ru' => ['nullable', 'string'],
                'become_supplier_welcome_cta_en' => ['nullable', 'string'],
                'become_supplier_welcome_cta_uk' => ['nullable', 'string'],

                // Become Supplier - Supplier Stats
                'become_supplier_stats_title_ru' => ['nullable', 'string'],
                'become_supplier_stats_title_en' => ['nullable', 'string'],
                'become_supplier_stats_title_uk' => ['nullable', 'string'],
                'become_supplier_stats_active_suppliers_ru' => ['nullable', 'string'],
                'become_supplier_stats_active_suppliers_en' => ['nullable', 'string'],
                'become_supplier_stats_active_suppliers_uk' => ['nullable', 'string'],
                'become_supplier_stats_total_sales_ru' => ['nullable', 'string'],
                'become_supplier_stats_total_sales_en' => ['nullable', 'string'],
                'become_supplier_stats_total_sales_uk' => ['nullable', 'string'],
                'become_supplier_stats_average_rating_ru' => ['nullable', 'string'],
                'become_supplier_stats_average_rating_en' => ['nullable', 'string'],
                'become_supplier_stats_average_rating_uk' => ['nullable', 'string'],
                'become_supplier_stats_countries_ru' => ['nullable', 'string'],
                'become_supplier_stats_countries_en' => ['nullable', 'string'],
                'become_supplier_stats_countries_uk' => ['nullable', 'string'],

                // Become Supplier - Process Steps
                'become_supplier_process_title_ru' => ['nullable', 'string'],
                'become_supplier_process_title_en' => ['nullable', 'string'],
                'become_supplier_process_title_uk' => ['nullable', 'string'],
                'become_supplier_process_step1_title_ru' => ['nullable', 'string'],
                'become_supplier_process_step1_title_en' => ['nullable', 'string'],
                'become_supplier_process_step1_title_uk' => ['nullable', 'string'],
                'become_supplier_process_step1_description_ru' => ['nullable', 'string'],
                'become_supplier_process_step1_description_en' => ['nullable', 'string'],
                'become_supplier_process_step1_description_uk' => ['nullable', 'string'],
                'become_supplier_process_step2_title_ru' => ['nullable', 'string'],
                'become_supplier_process_step2_title_en' => ['nullable', 'string'],
                'become_supplier_process_step2_title_uk' => ['nullable', 'string'],
                'become_supplier_process_step2_description_ru' => ['nullable', 'string'],
                'become_supplier_process_step2_description_en' => ['nullable', 'string'],
                'become_supplier_process_step2_description_uk' => ['nullable', 'string'],
                'become_supplier_process_step3_title_ru' => ['nullable', 'string'],
                'become_supplier_process_step3_title_en' => ['nullable', 'string'],
                'become_supplier_process_step3_title_uk' => ['nullable', 'string'],
                'become_supplier_process_step3_description_ru' => ['nullable', 'string'],
                'become_supplier_process_step3_description_en' => ['nullable', 'string'],
                'become_supplier_process_step3_description_uk' => ['nullable', 'string'],
                'become_supplier_process_step4_title_ru' => ['nullable', 'string'],
                'become_supplier_process_step4_title_en' => ['nullable', 'string'],
                'become_supplier_process_step4_title_uk' => ['nullable', 'string'],
                'become_supplier_process_step4_description_ru' => ['nullable', 'string'],
                'become_supplier_process_step4_description_en' => ['nullable', 'string'],
                'become_supplier_process_step4_description_uk' => ['nullable', 'string'],

                // Become Supplier - Digital Goods Categories
                'become_supplier_categories_title_ru' => ['nullable', 'string'],
                'become_supplier_categories_title_en' => ['nullable', 'string'],
                'become_supplier_categories_title_uk' => ['nullable', 'string'],
                'become_supplier_categories_subtitle_ru' => ['nullable', 'string'],
                'become_supplier_categories_subtitle_en' => ['nullable', 'string'],
                'become_supplier_categories_subtitle_uk' => ['nullable', 'string'],
                'become_supplier_categories_social_media_ru' => ['nullable', 'string'],
                'become_supplier_categories_social_media_en' => ['nullable', 'string'],
                'become_supplier_categories_social_media_uk' => ['nullable', 'string'],
                'become_supplier_categories_gaming_ru' => ['nullable', 'string'],
                'become_supplier_categories_gaming_en' => ['nullable', 'string'],
                'become_supplier_categories_gaming_uk' => ['nullable', 'string'],
                'become_supplier_categories_streaming_ru' => ['nullable', 'string'],
                'become_supplier_categories_streaming_en' => ['nullable', 'string'],
                'become_supplier_categories_streaming_uk' => ['nullable', 'string'],
                'become_supplier_categories_software_ru' => ['nullable', 'string'],
                'become_supplier_categories_software_en' => ['nullable', 'string'],
                'become_supplier_categories_software_uk' => ['nullable', 'string'],
                'become_supplier_categories_other_ru' => ['nullable', 'string'],
                'become_supplier_categories_other_en' => ['nullable', 'string'],
                'become_supplier_categories_other_uk' => ['nullable', 'string'],

                // Become Supplier - Restricted Items
                'become_supplier_restricted_title_ru' => ['nullable', 'string'],
                'become_supplier_restricted_title_en' => ['nullable', 'string'],
                'become_supplier_restricted_title_uk' => ['nullable', 'string'],
                'become_supplier_restricted_subtitle_ru' => ['nullable', 'string'],
                'become_supplier_restricted_subtitle_en' => ['nullable', 'string'],
                'become_supplier_restricted_subtitle_uk' => ['nullable', 'string'],
                'become_supplier_restricted_items_ru' => ['nullable', 'string'],
                'become_supplier_restricted_items_en' => ['nullable', 'string'],
                'become_supplier_restricted_items_uk' => ['nullable', 'string'],
                'become_supplier_restricted_contact_ru' => ['nullable', 'string'],
                'become_supplier_restricted_contact_en' => ['nullable', 'string'],
                'become_supplier_restricted_contact_uk' => ['nullable', 'string'],

                // Become Supplier - Partner Benefits
                'become_supplier_benefits_title_ru' => ['nullable', 'string'],
                'become_supplier_benefits_title_en' => ['nullable', 'string'],
                'become_supplier_benefits_title_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit1_title_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit1_title_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit1_title_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit1_description_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit1_description_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit1_description_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit2_title_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit2_title_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit2_title_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit2_description_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit2_description_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit2_description_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit3_title_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit3_title_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit3_title_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit3_description_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit3_description_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit3_description_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit4_title_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit4_title_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit4_title_uk' => ['nullable', 'string'],
                'become_supplier_benefits_benefit4_description_ru' => ['nullable', 'string'],
                'become_supplier_benefits_benefit4_description_en' => ['nullable', 'string'],
                'become_supplier_benefits_benefit4_description_uk' => ['nullable', 'string'],

                // Become Supplier - Payout Methods
                'become_supplier_payout_title_ru' => ['nullable', 'string'],
                'become_supplier_payout_title_en' => ['nullable', 'string'],
                'become_supplier_payout_title_uk' => ['nullable', 'string'],
                'become_supplier_payout_subtitle_ru' => ['nullable', 'string'],
                'become_supplier_payout_subtitle_en' => ['nullable', 'string'],
                'become_supplier_payout_subtitle_uk' => ['nullable', 'string'],
                'become_supplier_payout_methods_ru' => ['nullable', 'string'],
                'become_supplier_payout_methods_en' => ['nullable', 'string'],
                'become_supplier_payout_methods_uk' => ['nullable', 'string'],
                'become_supplier_payout_cta_ru' => ['nullable', 'string'],
                'become_supplier_payout_cta_en' => ['nullable', 'string'],
                'become_supplier_payout_cta_uk' => ['nullable', 'string'],

                // Become Supplier - FAQ
                'become_supplier_faq_title_ru' => ['nullable', 'string'],
                'become_supplier_faq_title_en' => ['nullable', 'string'],
                'become_supplier_faq_title_uk' => ['nullable', 'string'],
                'become_supplier_faq_question1_ru' => ['nullable', 'string'],
                'become_supplier_faq_question1_en' => ['nullable', 'string'],
                'become_supplier_faq_question1_uk' => ['nullable', 'string'],
                'become_supplier_faq_answer1_ru' => ['nullable', 'string'],
                'become_supplier_faq_answer1_en' => ['nullable', 'string'],
                'become_supplier_faq_answer1_uk' => ['nullable', 'string'],
                'become_supplier_faq_question2_ru' => ['nullable', 'string'],
                'become_supplier_faq_question2_en' => ['nullable', 'string'],
                'become_supplier_faq_question2_uk' => ['nullable', 'string'],
                'become_supplier_faq_answer2_ru' => ['nullable', 'string'],
                'become_supplier_faq_answer2_en' => ['nullable', 'string'],
                'become_supplier_faq_answer2_uk' => ['nullable', 'string'],
                'become_supplier_faq_question3_ru' => ['nullable', 'string'],
                'become_supplier_faq_question3_en' => ['nullable', 'string'],
                'become_supplier_faq_question3_uk' => ['nullable', 'string'],
                'become_supplier_faq_answer3_ru' => ['nullable', 'string'],
                'become_supplier_faq_answer3_en' => ['nullable', 'string'],
                'become_supplier_faq_answer3_uk' => ['nullable', 'string'],
                'become_supplier_faq_question4_ru' => ['nullable', 'string'],
                'become_supplier_faq_question4_en' => ['nullable', 'string'],
                'become_supplier_faq_question4_uk' => ['nullable', 'string'],
                'become_supplier_faq_answer4_ru' => ['nullable', 'string'],
                'become_supplier_faq_answer4_en' => ['nullable', 'string'],
                'become_supplier_faq_answer4_uk' => ['nullable', 'string'],
            ],
            default => [],
        };
    }
}

