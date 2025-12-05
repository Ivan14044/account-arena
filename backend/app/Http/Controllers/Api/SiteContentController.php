<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;

class SiteContentController extends Controller
{
    /**
     * Get all site content grouped by sections and languages
     */
    public function index()
    {
        $content = [
            'hero' => [
                'ru' => [
                    'title' => Option::get('hero_title_ru'),
                    'description' => Option::get('hero_description_ru'),
                    'button' => Option::get('hero_button_ru'),
                ],
                'en' => [
                    'title' => Option::get('hero_title_en'),
                    'description' => Option::get('hero_description_en'),
                    'button' => Option::get('hero_button_en'),
                ],
                'uk' => [
                    'title' => Option::get('hero_title_uk'),
                    'description' => Option::get('hero_description_uk'),
                    'button' => Option::get('hero_button_uk'),
                ],
            ],
            'about' => [
                'ru' => [
                    'title' => Option::get('about_title_ru'),
                    'description' => Option::get('about_description_ru'),
                ],
                'en' => [
                    'title' => Option::get('about_title_en'),
                    'description' => Option::get('about_description_en'),
                ],
                'uk' => [
                    'title' => Option::get('about_title_uk'),
                    'description' => Option::get('about_description_uk'),
                ],
            ],
            'promote' => [
                'ru' => [
                    'title' => Option::get('promote_title_ru'),
                    'access' => [
                        'title' => Option::get('promote_access_title_ru'),
                        'description' => Option::get('promote_access_description_ru'),
                    ],
                    'pricing' => [
                        'title' => Option::get('promote_pricing_title_ru'),
                        'description' => Option::get('promote_pricing_description_ru'),
                    ],
                    'refund' => [
                        'title' => Option::get('promote_refund_title_ru'),
                        'description' => Option::get('promote_refund_description_ru'),
                    ],
                    'activation' => [
                        'title' => Option::get('promote_activation_title_ru'),
                        'description' => Option::get('promote_activation_description_ru'),
                    ],
                    'support' => [
                        'title' => Option::get('promote_support_title_ru'),
                        'description' => Option::get('promote_support_description_ru'),
                    ],
                    'payment' => [
                        'title' => Option::get('promote_payment_title_ru'),
                        'description' => Option::get('promote_payment_description_ru'),
                    ],
                ],
                'en' => [
                    'title' => Option::get('promote_title_en'),
                    'access' => [
                        'title' => Option::get('promote_access_title_en'),
                        'description' => Option::get('promote_access_description_en'),
                    ],
                    'pricing' => [
                        'title' => Option::get('promote_pricing_title_en'),
                        'description' => Option::get('promote_pricing_description_en'),
                    ],
                    'refund' => [
                        'title' => Option::get('promote_refund_title_en'),
                        'description' => Option::get('promote_refund_description_en'),
                    ],
                    'activation' => [
                        'title' => Option::get('promote_activation_title_en'),
                        'description' => Option::get('promote_activation_description_en'),
                    ],
                    'support' => [
                        'title' => Option::get('promote_support_title_en'),
                        'description' => Option::get('promote_support_description_en'),
                    ],
                    'payment' => [
                        'title' => Option::get('promote_payment_title_en'),
                        'description' => Option::get('promote_payment_description_en'),
                    ],
                ],
                'uk' => [
                    'title' => Option::get('promote_title_uk'),
                    'access' => [
                        'title' => Option::get('promote_access_title_uk'),
                        'description' => Option::get('promote_access_description_uk'),
                    ],
                    'pricing' => [
                        'title' => Option::get('promote_pricing_title_uk'),
                        'description' => Option::get('promote_pricing_description_uk'),
                    ],
                    'refund' => [
                        'title' => Option::get('promote_refund_title_uk'),
                        'description' => Option::get('promote_refund_description_uk'),
                    ],
                    'activation' => [
                        'title' => Option::get('promote_activation_title_uk'),
                        'description' => Option::get('promote_activation_description_uk'),
                    ],
                    'support' => [
                        'title' => Option::get('promote_support_title_uk'),
                        'description' => Option::get('promote_support_description_uk'),
                    ],
                    'payment' => [
                        'title' => Option::get('promote_payment_title_uk'),
                        'description' => Option::get('promote_payment_description_uk'),
                    ],
                ],
            ],
            'steps' => [
                'ru' => [
                    'title' => Option::get('steps_title_ru'),
                    'description' => Option::get('steps_description_ru'),
                ],
                'en' => [
                    'title' => Option::get('steps_title_en'),
                    'description' => Option::get('steps_description_en'),
                ],
                'uk' => [
                    'title' => Option::get('steps_title_uk'),
                    'description' => Option::get('steps_description_uk'),
                ],
            ],
            'becomeSupplier' => [
                'ru' => [
                    'welcomeBanner' => [
                        'headline' => Option::get('become_supplier_welcome_headline_ru'),
                        'subtitle' => Option::get('become_supplier_welcome_subtitle_ru'),
                        'ctaButton' => Option::get('become_supplier_welcome_cta_ru'),
                    ],
                    'supplierStats' => [
                        'title' => Option::get('become_supplier_stats_title_ru'),
                        'activeSuppliers' => Option::get('become_supplier_stats_active_suppliers_ru'),
                        'totalSales' => Option::get('become_supplier_stats_total_sales_ru'),
                        'averageRating' => Option::get('become_supplier_stats_average_rating_ru'),
                        'countries' => Option::get('become_supplier_stats_countries_ru'),
                    ],
                    'processSteps' => [
                        'title' => Option::get('become_supplier_process_title_ru'),
                        'step1' => [
                            'title' => Option::get('become_supplier_process_step1_title_ru'),
                            'description' => Option::get('become_supplier_process_step1_description_ru'),
                        ],
                        'step2' => [
                            'title' => Option::get('become_supplier_process_step2_title_ru'),
                            'description' => Option::get('become_supplier_process_step2_description_ru'),
                        ],
                        'step3' => [
                            'title' => Option::get('become_supplier_process_step3_title_ru'),
                            'description' => Option::get('become_supplier_process_step3_description_ru'),
                        ],
                        'step4' => [
                            'title' => Option::get('become_supplier_process_step4_title_ru'),
                            'description' => Option::get('become_supplier_process_step4_description_ru'),
                        ],
                    ],
                    'digitalGoodsCategories' => [
                        'title' => Option::get('become_supplier_categories_title_ru'),
                        'subtitle' => Option::get('become_supplier_categories_subtitle_ru'),
                        'socialMedia' => Option::get('become_supplier_categories_social_media_ru'),
                        'gaming' => Option::get('become_supplier_categories_gaming_ru'),
                        'streaming' => Option::get('become_supplier_categories_streaming_ru'),
                        'software' => Option::get('become_supplier_categories_software_ru'),
                        'other' => Option::get('become_supplier_categories_other_ru'),
                    ],
                    'restrictedItems' => [
                        'title' => Option::get('become_supplier_restricted_title_ru'),
                        'subtitle' => Option::get('become_supplier_restricted_subtitle_ru'),
                        'items' => Option::get('become_supplier_restricted_items_ru') ? explode("\n", Option::get('become_supplier_restricted_items_ru')) : [],
                        'contactMessage' => Option::get('become_supplier_restricted_contact_ru'),
                    ],
                    'partnerBenefits' => [
                        'title' => Option::get('become_supplier_benefits_title_ru'),
                        'benefit1' => [
                            'title' => Option::get('become_supplier_benefits_benefit1_title_ru'),
                            'description' => Option::get('become_supplier_benefits_benefit1_description_ru'),
                        ],
                        'benefit2' => [
                            'title' => Option::get('become_supplier_benefits_benefit2_title_ru'),
                            'description' => Option::get('become_supplier_benefits_benefit2_description_ru'),
                        ],
                        'benefit3' => [
                            'title' => Option::get('become_supplier_benefits_benefit3_title_ru'),
                            'description' => Option::get('become_supplier_benefits_benefit3_description_ru'),
                        ],
                        'benefit4' => [
                            'title' => Option::get('become_supplier_benefits_benefit4_title_ru'),
                            'description' => Option::get('become_supplier_benefits_benefit4_description_ru'),
                        ],
                    ],
                    'payoutMethods' => [
                        'title' => Option::get('become_supplier_payout_title_ru'),
                        'subtitle' => Option::get('become_supplier_payout_subtitle_ru'),
                        'methods' => Option::get('become_supplier_payout_methods_ru') ? explode("\n", Option::get('become_supplier_payout_methods_ru')) : [],
                        'ctaButton' => Option::get('become_supplier_payout_cta_ru'),
                    ],
                    'faq' => [
                        'title' => Option::get('become_supplier_faq_title_ru'),
                        'question1' => [
                            'question' => Option::get('become_supplier_faq_question1_ru'),
                            'answer' => Option::get('become_supplier_faq_answer1_ru'),
                        ],
                        'question2' => [
                            'question' => Option::get('become_supplier_faq_question2_ru'),
                            'answer' => Option::get('become_supplier_faq_answer2_ru'),
                        ],
                        'question3' => [
                            'question' => Option::get('become_supplier_faq_question3_ru'),
                            'answer' => Option::get('become_supplier_faq_answer3_ru'),
                        ],
                        'question4' => [
                            'question' => Option::get('become_supplier_faq_question4_ru'),
                            'answer' => Option::get('become_supplier_faq_answer4_ru'),
                        ],
                    ],
                ],
                'en' => [
                    'welcomeBanner' => [
                        'headline' => Option::get('become_supplier_welcome_headline_en'),
                        'subtitle' => Option::get('become_supplier_welcome_subtitle_en'),
                        'ctaButton' => Option::get('become_supplier_welcome_cta_en'),
                    ],
                    'supplierStats' => [
                        'title' => Option::get('become_supplier_stats_title_en'),
                        'activeSuppliers' => Option::get('become_supplier_stats_active_suppliers_en'),
                        'totalSales' => Option::get('become_supplier_stats_total_sales_en'),
                        'averageRating' => Option::get('become_supplier_stats_average_rating_en'),
                        'countries' => Option::get('become_supplier_stats_countries_en'),
                    ],
                    'processSteps' => [
                        'title' => Option::get('become_supplier_process_title_en'),
                        'step1' => [
                            'title' => Option::get('become_supplier_process_step1_title_en'),
                            'description' => Option::get('become_supplier_process_step1_description_en'),
                        ],
                        'step2' => [
                            'title' => Option::get('become_supplier_process_step2_title_en'),
                            'description' => Option::get('become_supplier_process_step2_description_en'),
                        ],
                        'step3' => [
                            'title' => Option::get('become_supplier_process_step3_title_en'),
                            'description' => Option::get('become_supplier_process_step3_description_en'),
                        ],
                        'step4' => [
                            'title' => Option::get('become_supplier_process_step4_title_en'),
                            'description' => Option::get('become_supplier_process_step4_description_en'),
                        ],
                    ],
                    'digitalGoodsCategories' => [
                        'title' => Option::get('become_supplier_categories_title_en'),
                        'subtitle' => Option::get('become_supplier_categories_subtitle_en'),
                        'socialMedia' => Option::get('become_supplier_categories_social_media_en'),
                        'gaming' => Option::get('become_supplier_categories_gaming_en'),
                        'streaming' => Option::get('become_supplier_categories_streaming_en'),
                        'software' => Option::get('become_supplier_categories_software_en'),
                        'other' => Option::get('become_supplier_categories_other_en'),
                    ],
                    'restrictedItems' => [
                        'title' => Option::get('become_supplier_restricted_title_en'),
                        'subtitle' => Option::get('become_supplier_restricted_subtitle_en'),
                        'items' => Option::get('become_supplier_restricted_items_en') ? explode("\n", Option::get('become_supplier_restricted_items_en')) : [],
                        'contactMessage' => Option::get('become_supplier_restricted_contact_en'),
                    ],
                    'partnerBenefits' => [
                        'title' => Option::get('become_supplier_benefits_title_en'),
                        'benefit1' => [
                            'title' => Option::get('become_supplier_benefits_benefit1_title_en'),
                            'description' => Option::get('become_supplier_benefits_benefit1_description_en'),
                        ],
                        'benefit2' => [
                            'title' => Option::get('become_supplier_benefits_benefit2_title_en'),
                            'description' => Option::get('become_supplier_benefits_benefit2_description_en'),
                        ],
                        'benefit3' => [
                            'title' => Option::get('become_supplier_benefits_benefit3_title_en'),
                            'description' => Option::get('become_supplier_benefits_benefit3_description_en'),
                        ],
                        'benefit4' => [
                            'title' => Option::get('become_supplier_benefits_benefit4_title_en'),
                            'description' => Option::get('become_supplier_benefits_benefit4_description_en'),
                        ],
                    ],
                    'payoutMethods' => [
                        'title' => Option::get('become_supplier_payout_title_en'),
                        'subtitle' => Option::get('become_supplier_payout_subtitle_en'),
                        'methods' => Option::get('become_supplier_payout_methods_en') ? explode("\n", Option::get('become_supplier_payout_methods_en')) : [],
                        'ctaButton' => Option::get('become_supplier_payout_cta_en'),
                    ],
                    'faq' => [
                        'title' => Option::get('become_supplier_faq_title_en'),
                        'question1' => [
                            'question' => Option::get('become_supplier_faq_question1_en'),
                            'answer' => Option::get('become_supplier_faq_answer1_en'),
                        ],
                        'question2' => [
                            'question' => Option::get('become_supplier_faq_question2_en'),
                            'answer' => Option::get('become_supplier_faq_answer2_en'),
                        ],
                        'question3' => [
                            'question' => Option::get('become_supplier_faq_question3_en'),
                            'answer' => Option::get('become_supplier_faq_answer3_en'),
                        ],
                        'question4' => [
                            'question' => Option::get('become_supplier_faq_question4_en'),
                            'answer' => Option::get('become_supplier_faq_answer4_en'),
                        ],
                    ],
                ],
                'uk' => [
                    'welcomeBanner' => [
                        'headline' => Option::get('become_supplier_welcome_headline_uk'),
                        'subtitle' => Option::get('become_supplier_welcome_subtitle_uk'),
                        'ctaButton' => Option::get('become_supplier_welcome_cta_uk'),
                    ],
                    'supplierStats' => [
                        'title' => Option::get('become_supplier_stats_title_uk'),
                        'activeSuppliers' => Option::get('become_supplier_stats_active_suppliers_uk'),
                        'totalSales' => Option::get('become_supplier_stats_total_sales_uk'),
                        'averageRating' => Option::get('become_supplier_stats_average_rating_uk'),
                        'countries' => Option::get('become_supplier_stats_countries_uk'),
                    ],
                    'processSteps' => [
                        'title' => Option::get('become_supplier_process_title_uk'),
                        'step1' => [
                            'title' => Option::get('become_supplier_process_step1_title_uk'),
                            'description' => Option::get('become_supplier_process_step1_description_uk'),
                        ],
                        'step2' => [
                            'title' => Option::get('become_supplier_process_step2_title_uk'),
                            'description' => Option::get('become_supplier_process_step2_description_uk'),
                        ],
                        'step3' => [
                            'title' => Option::get('become_supplier_process_step3_title_uk'),
                            'description' => Option::get('become_supplier_process_step3_description_uk'),
                        ],
                        'step4' => [
                            'title' => Option::get('become_supplier_process_step4_title_uk'),
                            'description' => Option::get('become_supplier_process_step4_description_uk'),
                        ],
                    ],
                    'digitalGoodsCategories' => [
                        'title' => Option::get('become_supplier_categories_title_uk'),
                        'subtitle' => Option::get('become_supplier_categories_subtitle_uk'),
                        'socialMedia' => Option::get('become_supplier_categories_social_media_uk'),
                        'gaming' => Option::get('become_supplier_categories_gaming_uk'),
                        'streaming' => Option::get('become_supplier_categories_streaming_uk'),
                        'software' => Option::get('become_supplier_categories_software_uk'),
                        'other' => Option::get('become_supplier_categories_other_uk'),
                    ],
                    'restrictedItems' => [
                        'title' => Option::get('become_supplier_restricted_title_uk'),
                        'subtitle' => Option::get('become_supplier_restricted_subtitle_uk'),
                        'items' => Option::get('become_supplier_restricted_items_uk') ? explode("\n", Option::get('become_supplier_restricted_items_uk')) : [],
                        'contactMessage' => Option::get('become_supplier_restricted_contact_uk'),
                    ],
                    'partnerBenefits' => [
                        'title' => Option::get('become_supplier_benefits_title_uk'),
                        'benefit1' => [
                            'title' => Option::get('become_supplier_benefits_benefit1_title_uk'),
                            'description' => Option::get('become_supplier_benefits_benefit1_description_uk'),
                        ],
                        'benefit2' => [
                            'title' => Option::get('become_supplier_benefits_benefit2_title_uk'),
                            'description' => Option::get('become_supplier_benefits_benefit2_description_uk'),
                        ],
                        'benefit3' => [
                            'title' => Option::get('become_supplier_benefits_benefit3_title_uk'),
                            'description' => Option::get('become_supplier_benefits_benefit3_description_uk'),
                        ],
                        'benefit4' => [
                            'title' => Option::get('become_supplier_benefits_benefit4_title_uk'),
                            'description' => Option::get('become_supplier_benefits_benefit4_description_uk'),
                        ],
                    ],
                    'payoutMethods' => [
                        'title' => Option::get('become_supplier_payout_title_uk'),
                        'subtitle' => Option::get('become_supplier_payout_subtitle_uk'),
                        'methods' => Option::get('become_supplier_payout_methods_uk') ? explode("\n", Option::get('become_supplier_payout_methods_uk')) : [],
                        'ctaButton' => Option::get('become_supplier_payout_cta_uk'),
                    ],
                    'faq' => [
                        'title' => Option::get('become_supplier_faq_title_uk'),
                        'question1' => [
                            'question' => Option::get('become_supplier_faq_question1_uk'),
                            'answer' => Option::get('become_supplier_faq_answer1_uk'),
                        ],
                        'question2' => [
                            'question' => Option::get('become_supplier_faq_question2_uk'),
                            'answer' => Option::get('become_supplier_faq_answer2_uk'),
                        ],
                        'question3' => [
                            'question' => Option::get('become_supplier_faq_question3_uk'),
                            'answer' => Option::get('become_supplier_faq_answer3_uk'),
                        ],
                        'question4' => [
                            'question' => Option::get('become_supplier_faq_question4_uk'),
                            'answer' => Option::get('become_supplier_faq_answer4_uk'),
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($content);
    }
}
