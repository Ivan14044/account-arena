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
        ];

        return response()->json($content);
    }
}
