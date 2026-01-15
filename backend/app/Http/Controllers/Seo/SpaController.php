<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SpaController extends Controller
{
    /**
     * Отдает index.html с инжектированными мета-тегами для SPA-роутов
     */
    public function index(Request $request)
    {
        $indexPath = base_path('../frontend/dist/index.html');
        
        if (!file_exists($indexPath)) {
            $indexPath = '/var/www/account-arena/frontend/dist/index.html';
        }
        
        if (!file_exists($indexPath)) {
            $indexPath = public_path('../frontend/dist/index.html');
        }
        
        if (!file_exists($indexPath)) {
            abort(404, 'Frontend build not found');
        }
        
        $html = file_get_contents($indexPath);
        
        // Детекция языка
        $locale = $request->get('lang') ?: $request->cookie('locale') ?: substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
        if (!in_array($locale, ['ru', 'en', 'uk'])) $locale = 'ru';
        
        app()->setLocale($locale);
        
        $metaTags = $this->getMetaTagsForRoute($request, $locale);
        
        // Если сущность не найдена - отдаем честный 404 для поисковиков
        $status = 200;
        if (isset($metaTags['status']) && $metaTags['status'] === 404) {
            $status = 404;
            $metaTags = [
                'title' => '404 - Page Not Found',
                'description' => 'Sorry, the page you are looking for does not exist.',
                'robots' => 'noindex, follow'
            ];
        }
        
        if (!empty($metaTags)) {
            $html = $this->injectMetaTags($html, $metaTags, $locale);
        }
        
        return response($html, $status)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }
    
    private function getMetaTagsForRoute(Request $request, string $locale): array
    {
        $path = trim($request->path(), '/');
        
        // Товары
        if (preg_match('#^account/(.+)$#i', $path, $matches)) {
            return $this->getProductMetaTags($matches[1], $locale);
        }
        
        // Статьи (деталка)
        if (preg_match('#^articles/(\d+)$#i', $path, $matches)) {
            return $this->getArticleMetaTags((int)$matches[1], $locale);
        }

        // Категории
        if (preg_match('#^categories/(\d+)$#i', $path, $matches)) {
            return $this->getCategoryMetaTags((int)$matches[1], $locale);
        }
        
        // Главная
        if ($path === '' || $path === '/') {
            return $this->getHomeMetaTags($locale);
        }
        
        // Список статей
        if ($path === 'articles') {
            return $this->getArticlesListMetaTags($locale);
        }

        // Сервисные страницы
        if ($path === 'become-supplier') {
            return $this->getServicePageMetaTags('become-supplier', $locale);
        }
        if ($path === 'conditions') {
            return $this->getServicePageMetaTags('conditions', $locale);
        }
        if ($path === 'payment-refund') {
            return $this->getServicePageMetaTags('payment-refund', $locale);
        }
        if ($path === 'contacts') {
            return $this->getServicePageMetaTags('contacts', $locale);
        }
        
        return [];
    }

    private function getProductMetaTags(string $idOrSku, string $locale): array
    {
        try {
            $product = ServiceAccount::where('is_active', true)
                ->where(function($query) use ($idOrSku) {
                    $query->where('id', $idOrSku)->orWhere('sku', $idOrSku);
                })->first();
            
            if (!$product) {
                return ['status' => 404];
            }
            
            $title = $this->getLocalizedField($product, 'title', $locale);
            $desc = $this->getLocalizedField($product, 'meta_description', $locale) ?: Str::limit(strip_tags($this->getLocalizedField($product, 'description', $locale)), 160);
            
            // Микроразметка Product
            $schema = [
                '@context' => 'https://schema.org/',
                '@type' => 'Product',
                'name' => $title,
                'description' => $desc,
                'sku' => $product->sku ?: $product->id,
                'image' => $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : url('/img/logo_trans.webp'),
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'USD',
                    'price' => $product->price,
                    'availability' => 'https://schema.org/InStock',
                    'url' => url()->current()
                ]
            ];

            return [
                'title' => ($title ?: 'Product ' . $idOrSku) . ' - Account Arena',
                'h1' => $title,
                'description' => $desc,
                'og:title' => $title,
                'og:description' => $desc,
                'og:type' => 'product',
                'og:image' => $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : url('/img/logo_trans.webp'),
                'canonical' => url("/seo/products/{$product->id}"),
                'schema' => $schema
            ];
        } catch (\Exception $e) { return []; }
    }
    
    private function getArticleMetaTags(int $id, string $locale): array
    {
        try {
            $article = Article::where('status', 'published')->find($id);
            if (!$article) {
                return ['status' => 404];
            }
            
            $title = $article->translate('title', $locale);
            $desc = $article->translate('meta_description', $locale) ?: Str::limit(strip_tags($article->translate('content', $locale)), 160);
            
            // Микроразметка Article
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $title,
                'description' => $desc,
                'image' => $article->img ? url(Storage::url($article->img)) : url('/img/logo_trans.webp'),
                'author' => ['@type' => 'Organization', 'name' => 'Account Arena'],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'Account Arena',
                    'logo' => ['@type' => 'ImageObject', 'url' => url('/img/logo_trans.webp')]
                ],
                'datePublished' => $article->created_at->toIso8601String(),
                'dateModified' => $article->updated_at->toIso8601String()
            ];

            return [
                'title' => ($title ?: 'Article ' . $id) . ' - Account Arena',
                'h1' => $title,
                'description' => $desc,
                'og:title' => $title,
                'og:description' => $desc,
                'og:type' => 'article',
                'og:image' => $article->img ? url(Storage::url($article->img)) : url('/img/logo_trans.webp'),
                'canonical' => url("/seo/articles/{$id}"),
                'schema' => $schema
            ];
        } catch (\Exception $e) { return []; }
    }

    private function getCategoryMetaTags(int $id, string $locale): array
    {
        try {
            $category = Category::find($id);
            if (!$category) return [];
            
            $name = $category->translate('name', $locale);
            $desc = $category->translate('meta_description', $locale) ?: $name . ' - Account Arena';
            
            return [
                'title' => $name . ' - Account Arena',
                'h1' => $name,
                'description' => $desc,
                'og:title' => $name,
                'og:description' => $desc,
                'canonical' => url("/seo/categories/{$id}"),
            ];
        } catch (\Exception $e) { return []; }
    }
    
    private function getHomeMetaTags(string $locale): array
    {
        $titles = [
            'ru' => 'Account Arena - Маркетплейс цифровых аккаунтов',
            'en' => 'Account Arena - Digital Accounts Marketplace',
            'uk' => 'Account Arena - Маркетплейс цифрових акаунтів'
        ];
        
        $descriptions = [
            'ru' => 'Купите качественные аккаунты для игр, соцсетей и сервисов. Быстрая доставка, гарантия качества, лучшие цены на рынке.',
            'en' => 'Buy high-quality accounts for games, social networks and services. Fast delivery, quality guarantee, best prices on the market.',
            'uk' => 'Купуйте якісні акаунти для ігор, соцмереж та сервісів. Швидка доставка, гарантія якості, найкращі ціни на ринку.'
        ];

        return [
            'title' => $titles[$locale] ?? $titles['ru'],
            // Удаляем h1 отсюда, так как он есть в HeroSection.vue, чтобы избежать дублей
            'description' => $descriptions[$locale] ?? $descriptions['ru'],
            'og:title' => 'Account Arena',
            'og:description' => $descriptions[$locale] ?? $descriptions['ru'],
            'canonical' => url('/'),
        ];
    }
    
    private function getServicePageMetaTags(string $page, string $locale): array
    {
        $data = [
            'become-supplier' => [
                'ru' => ['title' => 'Стать поставщиком - Account Arena', 'desc' => 'Узнайте, как стать поставщиком и начать продавать свои аккаунты на Account Arena.'],
                'en' => ['title' => 'Become a Supplier - Account Arena', 'desc' => 'Learn how to become a supplier and start selling your accounts on Account Arena.'],
                'uk' => ['title' => 'Стати постачальником - Account Arena', 'desc' => 'Дізнайтеся, як стати постачальником і почати продавати свої акаунти на Account Arena.']
            ],
            'conditions' => [
                'ru' => ['title' => 'Условия замены - Account Arena', 'desc' => 'Ознакомьтесь с условиями замены цифровых товаров в нашем магазине.'],
                'en' => ['title' => 'Replacement Conditions - Account Arena', 'desc' => 'Read our digital goods replacement conditions.'],
                'uk' => ['title' => 'Умови заміни - Account Arena', 'desc' => 'Ознайомтеся з умовами заміни цифрових товарів у нашому магазині.']
            ],
            'payment-refund' => [
                'ru' => ['title' => 'Оплата и возврат - Account Arena', 'desc' => 'Информация о способах оплаты и политике возврата денежных средств.'],
                'en' => ['title' => 'Payment and Refund - Account Arena', 'desc' => 'Information about payment methods and refund policy.'],
                'uk' => ['title' => 'Оплата та повернення - Account Arena', 'desc' => 'Інформація про способи оплати та політику повернення коштів.']
            ],
            'contacts' => [
                'ru' => ['title' => 'Контакты - Account Arena', 'desc' => 'Свяжитесь с нами для получения поддержки или по вопросам сотрудничества.'],
                'en' => ['title' => 'Contacts - Account Arena', 'desc' => 'Contact us for support or cooperation inquiries.'],
                'uk' => ['title' => 'Контакти - Account Arena', 'desc' => 'Зв\'яжіться з нами для отримання підтримки або з питань співпраці.']
            ]
        ];

        $pageData = $data[$page][$locale] ?? $data[$page]['ru'];

        return [
            'title' => $pageData['title'],
            'h1' => $pageData['title'],
            'description' => $pageData['desc'],
            'og:title' => $pageData['title'],
            'og:description' => $pageData['desc'],
            'canonical' => url("/" . $page),
        ];
    }

    private function getArticlesListMetaTags(string $locale): array
    {
        $titles = [
            'ru' => 'Статьи - Account Arena',
            'en' => 'Articles - Account Arena',
            'uk' => 'Статті - Account Arena'
        ];
        
        $descriptions = [
            'ru' => 'Читайте полезные статьи и инструкции на Account Arena',
            'en' => 'Read useful articles and instructions on Account Arena',
            'uk' => 'Читайте корисні статті та інструкції на Account Arena'
        ];

        return [
            'title' => $titles[$locale] ?? $titles['ru'],
            'h1' => $titles[$locale] ?? $titles['ru'],
            'description' => $descriptions[$locale] ?? $descriptions['ru'],
            'canonical' => url('/seo/articles'), // Используем /seo/ путь для каноникала списка статей
        ];
    }
    
    private function injectMetaTags(string $html, array $metaTags, string $locale): string
    {
        // 0. Обновляем язык в теге HTML
        $html = preg_replace('/<html lang=".*?"/i', '<html lang="' . $locale . '"', $html);

        // 1. Очищаем ВСЕ возможные старые теги, чтобы избежать дублей
        $html = preg_replace('/<title>.*?<\/title>/is', '', $html);
        $html = preg_replace('/<meta name="description".*?>/is', '', $html);
        $html = preg_replace('/<meta property="og:.*?".*?>/is', '', $html);
        $html = preg_replace('/<meta name="twitter:.*?".*?>/is', '', $html);
        $html = preg_replace('/<link rel="canonical".*?>/is', '', $html);
        $html = preg_replace('/<link rel="alternate" hreflang=".*?".*?>/is', '', $html);
        $html = preg_replace('/<meta name="robots".*?>/is', '', $html);
        
        $headTags = [];
        
        // Title
        $titleText = $metaTags['title'] ?? 'Account Arena';
        $headTags[] = '<title>' . htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8') . '</title>';
        
        // Robots
        $headTags[] = '<meta name="robots" content="' . ($metaTags['robots'] ?? 'index, follow') . '">';
        
        // Description
        if (isset($metaTags['description'])) {
            $headTags[] = '<meta name="description" content="' . htmlspecialchars($metaTags['description'], ENT_QUOTES, 'UTF-8') . '">';
        }
        
        // Canonical (с учетом текущих query параметров для пагинации)
        if (isset($metaTags['canonical'])) {
            $canonicalUrl = $metaTags['canonical'];
            if (request()->has('page')) {
                $canonicalUrl .= (str_contains($canonicalUrl, '?') ? '&' : '?') . 'page=' . request()->get('page');
            }
            $headTags[] = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">';
        }
        
        // Open Graph & Twitter
        foreach (['og:title', 'og:description', 'og:type', 'og:image'] as $prop) {
            if (isset($metaTags[$prop])) {
                $content = htmlspecialchars($metaTags[$prop], ENT_QUOTES, 'UTF-8');
                $headTags[] = "<meta property=\"{$prop}\" content=\"{$content}\">";
                $twitterName = str_replace('og:', 'twitter:', $prop);
                $headTags[] = "<meta name=\"{$twitterName}\" content=\"{$content}\">";
            }
        }
        
        // Hreflang
        $locales = ['ru', 'en', 'uk'];
        $currentUrl = url()->current();
        
        // Очищаем URL от lang параметров для генерации чистых ссылок hreflang
        $cleanUrl = preg_replace('/[?&]lang=[^&]*/', '', $currentUrl);
        $cleanUrl = rtrim($cleanUrl, '?&');
        $hasQuery = str_contains($cleanUrl, '?');

        foreach ($locales as $loc) {
            $langUrl = $cleanUrl . ($hasQuery ? '&' : '?') . 'lang=' . $loc;
            $headTags[] = '<link rel="alternate" hreflang="' . $loc . '" href="' . htmlspecialchars($langUrl, ENT_QUOTES, 'UTF-8') . '">';
        }
        $headTags[] = '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8') . '">';
        
        // Микроразметка (JSON-LD)
        if (isset($metaTags['schema'])) {
            $headTags[] = '<script type="application/ld+json">' . json_encode($metaTags['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        }

        // Вставка в HEAD (сразу после <head>)
        $injectedHead = implode("\n    ", $headTags);
        if (preg_match('/<head>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $html = substr_replace($html, "\n    " . $injectedHead . "\n", $insertPos, 0);
        }
        
        // Вставка H1 в BODY (скрытый для SEO)
        if (isset($metaTags['h1'])) {
            $h1Html = "\n  " . '<h1 style="display:none">' . htmlspecialchars($metaTags['h1'], ENT_QUOTES, 'UTF-8') . '</h1>' . "\n";
            if (preg_match('/<body[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPos = $matches[0][1] + strlen($matches[0][0]);
                $html = substr_replace($html, $h1Html, $insertPos, 0);
            }
        }
        
        return $html;
    }
    
    private function getLocalizedField($model, string $field, string $locale): ?string
    {
        $localizedField = ($locale === 'ru') ? $field : $field . '_' . $locale;
        return $model->$localizedField ?: $model->$field;
    }
}
