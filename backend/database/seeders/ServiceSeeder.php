<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\ServiceTranslation;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [
            'chatgpt' => [
                'ru' => [
                    'name' => 'ChatGPT',
                    'description' => "Передовая модель искусственного интеллекта для создания контента, помощи в программировании и креативного письма. Работает на передовой технологии OpenAI."
                ],
                'en' => [
                    'name' => 'ChatGPT',
                    'description' => "Advanced AI language model for content creation, coding assistance, and creative writing. Powered by OpenAI's cutting-edge technology."
                ],
                'uk' => [
                    'name' => 'ChatGPT',
                    'description' => "Передова модель штучного інтелекту для створення контенту, допомоги в програмуванні та креативного письма. Працює на передовій технології OpenAI."
                ]
            ],
            'adspy' => [
                'ru' => [
                    'name' => 'AdSpy',
                    'description' => "Крупнейшая база данных рекламы Facebook и Instagram. Находите успешные рекламные креативы и следите за стратегиями конкурентов."
                ],
                'en' => [
                    'name' => 'AdSpy',
                    'description' => "World's largest searchable database of Facebook and Instagram ads. Find winning ad creatives and spy on competitors' strategies."
                ],
                'uk' => [
                    'name' => 'AdSpy',
                    'description' => "Найбільша база даних реклами Facebook та Instagram. Знаходьте успішні рекламні креативи та слідкуйте за стратегіями конкурентів."
                ]
            ],
            'pipiads' => [
                'ru' => [
                    'name' => 'PipiAds',
                    'description' => "Комплексный инструмент анализа рекламы для TikTok и Meta. Отслеживайте популярную рекламу, анализируйте успешные кампании и находите прибыльные продукты."
                ],
                'en' => [
                    'name' => 'PipiAds',
                    'description' => "Comprehensive ad intelligence tool for TikTok and Meta. Track trending ads, analyze successful campaigns, and discover winning products."
                ],
                'uk' => [
                    'name' => 'PipiAds',
                    'description' => "Комплексний інструмент аналізу реклами для TikTok та Meta. Відстежуйте популярну рекламу, аналізуйте успішні кампанії та знаходьте прибуткові продукти."
                ]
            ],
            'canvapro' => [
                'ru' => [
                    'name' => 'Canva Pro',
                    'description' => "Премиум платформа для дизайна с расширенными функциями, Brand Kit, удалением фона и неограниченным премиум контентом для профессиональных дизайнеров."
                ],
                'en' => [
                    'name' => 'Canva Pro',
                    'description' => "Premium design platform with advanced features, Brand Kit, background remover, and unlimited premium content for professional designers."
                ],
                'uk' => [
                    'name' => 'Canva Pro',
                    'description' => "Преміум платформа для дизайну з розширеними функціями, Brand Kit, видаленням фону та необмеженим преміум контентом для професійних дизайнерів."
                ]
            ],
            'adheart' => [
                'ru' => [
                    'name' => 'Adheart',
                    'description' => "Передовой инструмент исследования рекламы для Facebook и Instagram. Мониторинг конкурентов, поиск прибыльной рекламы и анализ маркетинговых стратегий."
                ],
                'en' => [
                    'name' => 'Adheart',
                    'description' => "Advanced ad research tool for Facebook and Instagram. Monitor competitors, find profitable ads, and analyze marketing strategies."
                ],
                'uk' => [
                    'name' => 'Adheart',
                    'description' => "Передовий інструмент дослідження реклами для Facebook та Instagram. Моніторинг конкурентів, пошук прибуткової реклами та аналіз маркетингових стратегій."
                ]
            ]
        ];

        foreach ($services as $code => $translations) {
            $service = Service::create([
                'code' => $code,
                'amount' => 10,
                'trial_amount' => 1,
                'logo' => '/img/no-logo.png',
                'position' => 1
            ]);

            foreach ($translations as $locale => $data) {
                foreach ($data as $key => $value) {
                    ServiceTranslation::create([
                        'service_id' => $service->id,
                        'locale' => $locale,
                        'code' => $key,
                        'value' => $value,
                    ]);
                }
            }
        }
    }
}
