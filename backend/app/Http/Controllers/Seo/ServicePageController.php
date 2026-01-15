<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServicePageController extends Controller
{
    /**
     * Маппинг slug страниц на их идентификаторы
     */
    private function getPageSlugMap(): array
    {
        return [
            'suppliers' => 'become-supplier',
            'become-supplier' => 'become-supplier',
            'replace-conditions' => 'conditions',
            'conditions' => 'conditions',
            'payment-refund' => 'payment-refund',
            'contacts' => 'contacts',
        ];
    }

    /**
     * Получить страницу по slug
     */
    private function getPageBySlug(string $slug): ?Page
    {
        $slugMap = $this->getPageSlugMap();
        $actualSlug = $slugMap[$slug] ?? $slug;
        
        return Page::where('slug', $actualSlug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Получить локализованное поле страницы
     */
    private function getLocalizedField(Page $page, string $field, string $locale): ?string
    {
        return $page->translate($field, $locale);
    }

    /**
     * Получить альтернативные URL для hreflang
     */
    private function getAlternateUrls(string $routeName, array $params = []): array
    {
        $locales = ['ru', 'en', 'uk'];
        $urls = [];
        
        foreach ($locales as $locale) {
            $urls[$locale] = route($routeName, array_merge($params, ['lang' => $locale]));
        }
        
        return $urls;
    }

    /**
     * Получить fallback-контент из i18n
     */
    private function getFallbackContent(string $slug, string $locale): array
    {
        $fallbacks = [
            'suppliers' => [
                'ru' => ['title' => 'Стать поставщиком', 'description' => 'Узнайте, как стать поставщиком и начать продавать свои аккаунты на Account Arena.'],
                'en' => ['title' => 'Become a Supplier', 'description' => 'Learn how to become a supplier and start selling your accounts on Account Arena.'],
                'uk' => ['title' => 'Стати постачальником', 'description' => 'Дізнайтеся, як стати постачальником і почати продавати свої акаунти на Account Arena.'],
            ],
            'replace-conditions' => [
                'ru' => ['title' => 'Условия замены', 'description' => 'Ознакомьтесь с условиями замены цифровых товаров в нашем магазине.'],
                'en' => ['title' => 'Replacement Conditions', 'description' => 'Learn about the replacement conditions for digital goods in our store.'],
                'uk' => ['title' => 'Умови заміни', 'description' => 'Ознайомтеся з умовами заміни цифрових товарів у нашому магазині.'],
            ],
            'payment-refund' => [
                'ru' => ['title' => 'Оплата и возврат', 'description' => 'Информация о способах оплаты и условиях возврата средств.'],
                'en' => ['title' => 'Payment and Refund', 'description' => 'Information about payment methods and refund conditions.'],
                'uk' => ['title' => 'Оплата та повернення', 'description' => 'Інформація про способи оплати та умови повернення коштів.'],
            ],
        ];
        
        $key = $slug === 'suppliers' ? 'suppliers' : ($slug === 'replace-conditions' ? 'replace-conditions' : 'payment-refund');
        return $fallbacks[$key][$locale] ?? $fallbacks[$key]['ru'];
    }

    /**
     * Общий метод для отображения сервисной страницы
     */
    private function showServicePage(string $slug, string $routeName): \Illuminate\View\View
    {
        $locale = app()->getLocale();
        
        $page = $this->getPageBySlug($slug);
        
        if (!$page) {
            abort(404);
        }
        
        // Получаем локализованные поля
        $title = $this->getLocalizedField($page, 'title', $locale);
        $content = $this->getLocalizedField($page, 'content', $locale);
        $metaTitle = $this->getLocalizedField($page, 'meta_title', $locale);
        $metaDescription = $this->getLocalizedField($page, 'meta_description', $locale);
        
        // Fallback на i18n, если контент пуст
        if (empty($title) || empty($content)) {
            $fallback = $this->getFallbackContent($slug, $locale);
            $title = $title ?: $fallback['title'];
            $content = $content ?: '<p>' . $fallback['description'] . '</p>';
            $metaDescription = $metaDescription ?: $fallback['description'];
        }
        
        $metaTitle = $metaTitle ?: $title;
        $metaDescription = $metaDescription ?: Str::limit(strip_tags($content ?: $title), 160);
        
        // Формируем уникальный title
        $pageTitle = $metaTitle . ' - Account Arena';
        
        // Hreflang альтернативные URL
        $alternateUrls = $this->getAlternateUrls($routeName);
        
        // SPA версия для пользователей (alternate)
        $spaUrl = url('/' . $slug);
        
        // Структурированные данные (WebPage Schema + FAQPage)
        $structuredData = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'description' => $metaDescription,
                'url' => url()->current(),
                'inLanguage' => $locale,
            ]
        ];
        
        // Добавляем FAQPage разметку, если есть FAQ в контенте
        // Можно расширить позже, добавив FAQ в БД
        $faqData = $this->getFAQStructuredData($slug, $locale);
        if ($faqData) {
            $structuredData[] = $faqData;
        }
        
        return view('seo.service-page', compact(
            'page',
            'title',
            'content',
            'metaTitle',
            'metaDescription',
            'pageTitle',
            'locale',
            'alternateUrls',
            'structuredData',
            'spaUrl'
        ));
    }
    
    /**
     * Получить FAQ структурированные данные
     */
    private function getFAQStructuredData(string $slug, string $locale): ?array
    {
        // Базовые FAQ для сервисных страниц
        $faqs = [
            'suppliers' => [
                'ru' => [
                    ['question' => 'Как быстро я начну получать доход?', 'answer' => 'После регистрации и добавления товаров они сразу появятся в каталоге. Первые продажи могут начаться уже в день регистрации.'],
                    ['question' => 'Какая комиссия платформы?', 'answer' => 'Мы добавляем наценку к вашей цене. Вы получаете полную сумму, которую установили. Прозрачные условия без скрытых комиссий.'],
                ],
                'en' => [
                    ['question' => 'How quickly will I start earning income?', 'answer' => 'After registration and adding products, they will immediately appear in the catalog. First sales can start on the day of registration.'],
                    ['question' => 'What is the platform commission?', 'answer' => 'We add a markup to your price. You receive the full amount you set. Transparent conditions without hidden commissions.'],
                ],
                'uk' => [
                    ['question' => 'Як швидко я почну отримувати дохід?', 'answer' => 'Після реєстрації та додавання товарів вони одразу з\'являться в каталозі. Перші продажі можуть початися вже в день реєстрації.'],
                    ['question' => 'Яка комісія платформи?', 'answer' => 'Ми додаємо націнку до вашої ціни. Ви отримуєте повну суму, яку встановили. Прозорі умови без прихованих комісій.'],
                ],
            ],
            'payment-refund' => [
                'ru' => [
                    ['question' => 'Какие способы оплаты доступны?', 'answer' => 'Мы принимаем оплату банковскими картами (Visa, Mastercard), криптовалютой (Bitcoin, Ethereum, USDT) и другими способами.'],
                    ['question' => 'Как вернуть деньги?', 'answer' => 'Возврат средств возможен в течение 14 дней с момента покупки при наличии обоснованной причины. Обратитесь в службу поддержки.'],
                ],
                'en' => [
                    ['question' => 'What payment methods are available?', 'answer' => 'We accept payment by bank cards (Visa, Mastercard), cryptocurrency (Bitcoin, Ethereum, USDT) and other methods.'],
                    ['question' => 'How to get a refund?', 'answer' => 'Refund is possible within 14 days of purchase if there is a valid reason. Contact support.'],
                ],
                'uk' => [
                    ['question' => 'Які способи оплати доступні?', 'answer' => 'Ми приймаємо оплату банківськими картками (Visa, Mastercard), криптовалютою (Bitcoin, Ethereum, USDT) та іншими способами.'],
                    ['question' => 'Як повернути гроші?', 'answer' => 'Повернення коштів можливе протягом 14 днів з моменту покупки за наявності обґрунтованої причини. Зверніться до служби підтримки.'],
                ],
            ],
        ];
        
        if (!isset($faqs[$slug][$locale])) {
            return null;
        }
        
        $faqItems = [];
        foreach ($faqs[$slug][$locale] as $faq) {
            $faqItems[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqItems
        ];
    }

    /**
     * Показать страницу "Поставщикам"
     */
    public function suppliers(Request $request)
    {
        return $this->showServicePage('suppliers', 'seo.suppliers');
    }

    /**
     * Показать страницу "Условия замены"
     */
    public function replaceConditions(Request $request)
    {
        return $this->showServicePage('replace-conditions', 'seo.replace-conditions');
    }

    /**
     * Показать страницу "Оплата и возврат"
     */
    public function paymentRefund(Request $request)
    {
        return $this->showServicePage('payment-refund', 'seo.payment-refund');
    }
}
