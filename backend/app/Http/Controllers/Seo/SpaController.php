<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
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
        
        // Алиасы для товаров
        if (preg_match('#^(account|products)/(.+)$#i', $path, $matches)) {
            return $this->getProductMetaTags($matches[2], $locale, $matches[1]);
        }
        
        // Статьи (деталка)
        if (preg_match('#^articles/(\d+)$#i', $path, $matches)) {
            return $this->getArticleMetaTags((int)$matches[1], $locale);
        }

        // Категории
        if (preg_match('#^categories/(.+)$#i', $path, $matches)) {
            // Исключаем пагинацию из slug (если URL вида categories/slug/page/2)
            // Но Laravel роуты обычно такие вещи разруливают, тут path - это полный путь
            // Если путь categories/123/page/2 - то matches[1] будет 123/page/2
            // Нам нужно отсечь page
            $slugOrId = $matches[1];
            if (preg_match('/^(.*?)\/page\/\d+$/', $slugOrId, $pageMatches)) {
                $slugOrId = $pageMatches[1];
            }
            return $this->getCategoryMetaTags($slugOrId, $locale);
        }

        // Список категорий
        if ($path === 'categories') {
            return $this->getCategoriesListMetaTags($locale);
        }
        
        // Главная
        if ($path === '' || $path === '/') {
            return $this->getHomeMetaTags($locale);
        }
        
        // Список статей
        if ($path === 'articles') {
            return $this->getArticlesListMetaTags($locale);
        }

        // Info pages
        $infoPages = [
            'faq' => 'faq',
            'guarantees' => 'guarantees',
            'cookies' => 'cookies',
            'terms' => 'terms',
            'privacy' => 'privacy'
        ];

        if (isset($infoPages[$path])) {
            return $this->getInfoPageMetaTags($infoPages[$path], $locale);
        }

        // Service aliases (existing)
        $serviceAliases = [
            'suppliers' => 'become-supplier',
            'become-supplier' => 'become-supplier',
            'conditions' => 'conditions',
            'replace-conditions' => 'conditions',
            'payment-refund' => 'payment-refund',
            'contacts' => 'contacts'
        ];

        if (isset($serviceAliases[$path])) {
            return $this->getServicePageMetaTags($serviceAliases[$path], $locale, $path);
        }

        // Dynamic pages
        $dynamicTags = $this->getDynamicPageMetaTags($path, $locale);
        if (!empty($dynamicTags)) {
            return $dynamicTags;
        }

        // If nothing matched, return 404
        // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ SEO: noindex для 404 страниц
        return [
            'status' => 404,
            'robots' => 'noindex, follow',
            'title' => '404 - Page Not Found',
            'description' => 'Sorry, the page you are looking for does not exist.'
        ];
    }

    private function getProductMetaTags(string $idOrSku, string $locale, string $requestPrefix = 'account'): array
    {
        try {
            $product = ServiceAccount::where('is_active', true)
                ->where(function($query) use ($idOrSku) {
                    $query->where('id', $idOrSku)
                          ->orWhere('sku', $idOrSku)
                          ->orWhere('slug', $idOrSku);
                })->first();
            
            if (!$product) {
                return ['status' => 404];
            }
            
            $title = $this->getLocalizedField($product, 'title', $locale);
            $rawDesc = $this->getLocalizedField($product, 'description', $locale);
            // Очищаем описание для мета-тегов (URL + эмодзи)
            $cleanDesc = $this->sanitizeMetaDescription($rawDesc);
            $desc = $this->getLocalizedField($product, 'meta_description', $locale)
                ?: $this->smartTruncate(strip_tags($cleanDesc), 160);
            
            // Микроразметка Breadcrumbs
            $breadcrumbs = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => url('/')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Products',
                        'item' => url('/categories')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $title,
                        'item' => rtrim(url("/products/" . ($product->slug ?: $product->id)), '/')
                    ]
                ]
            ];

            // Микроразметка Product
            $productSchema = [
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

            // Объединяем схемы через @graph для лучшей валидации
            $schema = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    $productSchema,
                    $breadcrumbs
                ]
            ];

             // Генерируем HTML контент для бота
             $htmlContent = $this->generateProductContent($product, $title, $rawDesc, $locale);
 
             return [
                 'title' => ($title ?: 'Product ' . $idOrSku) . ' - Account Arena',
                 'h1' => $title,
                 'description' => $desc,
                 'og:title' => $title,
                 'og:description' => $desc,
                 'og:type' => 'product',
                 'og:image' => $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : url('/img/logo_trans.webp'),
                 'canonical' => rtrim(url("/products/" . ($product->slug ?: $product->id)), '/'),
                 'schema' => $schema,
                 'html_content' => $htmlContent
             ];
        } catch (\Exception $e) { return []; }
    }

    /**
     * Санитизация описания для мета-тегов: удаляем URL и эмодзи
     */
    private function sanitizeMetaDescription(?string $description): string
    {
        if (!$description) {
            return '';
        }

        // Удаляем URL из описания
        $text = preg_replace('/https?:\/\/\S+/i', '', $description);
        // Удаляем эмодзи для более «делового» сниппета
        $text = preg_replace('/[\x{1F300}-\x{1F6FF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', (string)$text);

        return trim($text);
    }

    private function getArticleMetaTags(int $id, string $locale): array
    {
        try {
            $article = Article::where('status', 'published')->find($id);
            if (!$article) {
                return ['status' => 404];
            }
            
            $title = $article->translate('title', $locale);
            $desc = $article->translate('meta_description', $locale) ?: $this->smartTruncate(strip_tags($article->translate('content', $locale)), 160);
            
            // Микроразметка Article
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $title,
                'description' => $desc,
                'image' => $article->img ? $this->normalizeArticleImageUrl($article->img) : url('/img/logo_trans.webp'),
                'author' => ['@type' => 'Organization', 'name' => 'Account Arena'],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'Account Arena',
                    'logo' => ['@type' => 'ImageObject', 'url' => url('/img/logo_trans.webp')]
                ],
                'datePublished' => $article->created_at->toIso8601String(),
                'dateModified' => $article->updated_at->toIso8601String()
            ];

            // Микроразметка Breadcrumbs
            $breadcrumbs = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => url('/')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Articles',
                        'item' => url('/articles')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $title,
                        'item' => rtrim(url("/articles/{$id}"), '/')
                    ]
                ]
            ];

            // Объединяем схемы через @graph
            $fullSchema = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    $schema,
                    $breadcrumbs
                ]
            ];

            // Generate Content for Injection
            $content = $article->translate('content', $locale);
            $image = $article->img ? $this->normalizeArticleImageUrl($article->img) : url('/img/logo_trans.webp');
            $htmlContent = $this->generateArticleContent($title, $content, $article->created_at->toIso8601String(), $image);

            return [
                'title' => ($title ?: 'Article ' . $id) . ' - Account Arena',
                'h1' => $title,
                'description' => $desc,
                'og:title' => $title,
                'og:description' => $desc,
                'og:type' => 'article',
                'og:image' => $image,
                'canonical' => rtrim(url("/articles/{$id}"), '/'),
                'schema' => $fullSchema,
                'html_content' => $htmlContent
            ];
        } catch (\Exception $e) { return []; }
    }

    /**
     * Нормализация URL изображения статьи (без двойного /storage)
     */
    private function normalizeArticleImageUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Убираем двойной /storage/ если он есть
        $path = preg_replace('#/storage//storage/#', '/storage/', $path);
        $path = preg_replace('#^storage//storage/#', 'storage/', $path);
        
        // Нормализуем путь: убираем лишние слэши
        $normalized = '/' . ltrim($path, '/');
        
        // Если путь уже содержит storage, не добавляем повторно через Storage::url
        if (str_starts_with(ltrim($path, '/'), 'storage/')) {
            return url($normalized);
        }

        $storageUrl = Storage::url($path);
        // Дополнительная проверка на двойной /storage/ после Storage::url
        $storageUrl = preg_replace('#/storage//storage/#', '/storage/', $storageUrl);
        
        return url($storageUrl);
    }

    private function getCategoryMetaTags($idOrSlug, string $locale): array
    {
        try {
            if (is_numeric($idOrSlug)) {
                $category = Category::find($idOrSlug);
            } else {
                $category = Category::where('slug', $idOrSlug)->first();
            }
            
            if (!$category) {
                return ['status' => 404];
            }
            
            $id = $category->id; // Для fallback описания
            
            $name = $category->translate('name', $locale);
            if (empty($name)) {
                $name = 'Category ' . $id;
            }
            
            $desc = $category->translate('meta_description', $locale);
            
            // Очищаем дублирование слова "accounts" если оно есть в сохраненном описании
            if ($desc) {
                $desc = preg_replace('/\b(accounts|аккаунты|акаунти)\s+\1\b/iu', '$1', $desc);
            }
            
            if ($this->isDescriptionTooShort($desc)) {
                // Генерируем осмысленное описание на основе названия категории
                $desc = $this->getCategoryDescription($name, $locale);
            }
            
            // Генерируем HTML описание категории
            $htmlContent = $this->generateCategoryContent($name, $desc, $locale);

            // Микроразметка Breadcrumbs
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => url('/')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Categories',
                        'item' => url('/categories')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $name,
                        'item' => rtrim(url("/categories/" . ($category->slug ?: $category->id)), '/')
                    ]
                ]
            ];

            return [
                'title' => $name . ' - Account Arena',
                'h1' => $name,
                'description' => $desc,
                'og:title' => $name,
                'og:description' => $desc,
                'canonical' => rtrim(url("/categories/" . ($category->slug ?: $category->id)), '/'),
                'html_content' => $htmlContent,
                'schema' => $schema
            ];
        } catch (\Exception $e) { 
            return ['status' => 404];
        }
    }

    /**
     * Мета-теги для списка категорий
     */
    private function getCategoriesListMetaTags(string $locale): array
    {
        $titles = [
            'ru' => 'Категории аккаунтов - Account Arena',
            'en' => 'Account Categories - Account Arena',
            'uk' => 'Категорії акаунтів - Account Arena'
        ];

        $descriptions = [
            'ru' => 'Просмотрите категории аккаунтов и выберите подходящие предложения на Account Arena.',
            'en' => 'Browse account categories and choose suitable offers on Account Arena.',
            'uk' => 'Перегляньте категорії акаунтів та оберіть відповідні пропозиції на Account Arena.'
        ];

        $title = $titles[$locale] ?? $titles['ru'];
        $description = $descriptions[$locale] ?? $descriptions['ru'];

        // Generate Content (List of all active categories)
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $htmlContent = $this->generateCategoriesListContent($title, $description, $categories, $locale);

        return [
            'title' => $title,
            'h1' => $title,
            'description' => $description,
            'og:title' => $title,
            'og:description' => $description,
            'canonical' => rtrim(url('/categories'), '/'),
            'html_content' => $htmlContent
        ];
    }

    /**
     * Проверяем, что описание не слишком короткое
     */
    private function isDescriptionTooShort(?string $description): bool
    {
        $text = trim((string)$description);
        return $text === '' || mb_strlen($text) < 40;
    }

    /**
     * Генерация осмысленного описания для категории
     */
    private function getCategoryDescription(string $name, string $locale): string
    {
        // Проверяем, содержит ли название уже слово "accounts" (в разных вариантах)
        $nameLower = mb_strtolower($name);
        $hasAccounts = str_contains($nameLower, 'accounts') || str_contains($nameLower, 'аккаунты') || str_contains($nameLower, 'акаунти');
        
        if ($hasAccounts) {
            // Если название уже содержит "accounts", не добавляем его повторно
            $templates = [
                'ru' => 'Купить ' . $name . ' — описание категории, варианты и актуальные предложения на Account Arena.',
                'en' => 'Buy ' . $name . ' — category overview, options and current offers on Account Arena.',
                'uk' => 'Купити ' . $name . ' — опис категорії, варіанти та актуальні пропозиції на Account Arena.'
            ];
        } else {
            // Если названия нет, добавляем "аккаунты"
            $templates = [
                'ru' => 'Купить аккаунты ' . $name . ' — описание категории, варианты и актуальные предложения на Account Arena.',
                'en' => 'Buy ' . $name . ' accounts — category overview, options and current offers on Account Arena.',
                'uk' => 'Купити акаунти ' . $name . ' — опис категорії, варіанти та актуальні пропозиції на Account Arena.'
            ];
        }

        $description = $templates[$locale] ?? $templates['ru'];
        
        // Дополнительная очистка: убираем дублирование слова "accounts" если оно есть
        $description = preg_replace('/\b(accounts|аккаунты|акаунти)\s+\1\b/iu', '$1', $description);
        
        return $description;
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

        $title = $titles[$locale] ?? $titles['ru'];
        $description = $descriptions[$locale] ?? $descriptions['ru'];
        
        // Organization Schema for Home Page
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Account Arena',
            'url' => url('/'),
            'logo' => url('/img/logo_trans.webp'),
            'sameAs' => [
                'https://t.me/account_arena_bot' // Telegram Bot
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'url' => 'https://t.me/account_arena_support'
            ]
        ];

        // Generate Home Content (Categories, Top Products, Recent Articles for Bots)
        $htmlContent = $this->generateHomeContent($title, $description, $locale);

        return [
            'title' => $title,
            'description' => $description,
            'og:title' => $title, 
            'og:description' => $description,
            'canonical' => rtrim(url('/'), '/'),
            'schema' => $schema,
            'html_content' => $htmlContent
        ];
    }

    /**
     * Генерирует расширенный контент для главной страницы (для поисковых роботов)
     */
    private function generateHomeContent($title, $description, $locale)
    {
        $html = '<div class="home-seo-content" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        
        if ($description) {
            $html .= '<p class="description" style="margin-top: 15px; line-height: 1.6;">' . htmlspecialchars($description) . '</p>';
        }

        // 1. Секция категорий (Только родительские товарные категории)
        $categories = Category::productCategories()->parentCategories()->limit(20)->get();
        if ($categories->count() > 0) {
            $catLabel = [
                'ru' => 'Популярные категории аккаунтов:',
                'en' => 'Popular Account Categories:',
                'uk' => 'Популярні категорії акаунтів:'
            ];
            $html .= '<section style="margin-top: 30px;">';
            $html .= '<h2>' . ($catLabel[$locale] ?? $catLabel['ru']) . '</h2>';
            $html .= '<ul style="columns: 2; -webkit-columns: 2; -moz-columns: 2;">';
            foreach ($categories as $cat) {
                $catName = $cat->translate('name', $locale) ?: $cat->name;
                $catUrl = url('/categories/' . ($cat->slug ?: $cat->id));
                $html .= '<li><a href="' . $catUrl . '">' . htmlspecialchars($catName) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</section>';
        }

        // 2. Секция популярных товаров (По просмотрам)
        $products = ServiceAccount::where('is_active', true)
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get();
            
        if ($products->count() > 0) {
            $prodLabel = [
                'ru' => 'Топ товаров сегодня:',
                'en' => 'Top products today:',
                'uk' => 'Топ товарів сьогодні:'
            ];
            $html .= '<section style="margin-top: 30px;">';
            $html .= '<h2>' . ($prodLabel[$locale] ?? $prodLabel['ru']) . '</h2>';
            $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">';
            foreach ($products as $product) {
                $prodName = $this->getLocalizedField($product, 'title', $locale);
                $prodUrl = url('/products/' . ($product->slug ?: $product->id));
                $html .= '<div style="border: 1px solid #eee; padding: 10px;">';
                $html .= '<strong><a href="' . $prodUrl . '">' . htmlspecialchars($prodName) . '</a></strong><br>';
                $html .= '<span>Price: ' . $product->price . ' USD</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</section>';
        }

        // 3. Последние статьи блога
        $articles = Article::where('status', 'published')->orderBy('created_at', 'desc')->limit(5)->get();
        if ($articles->count() > 0) {
            $artLabel = [
                'ru' => 'Полезные инструкции и новости:',
                'en' => 'Useful instructions and news:',
                'uk' => 'Корисні інструкції та новини:'
            ];
            $html .= '<section style="margin-top: 30px;">';
            $html .= '<h2>' . ($artLabel[$locale] ?? $artLabel['ru']) . '</h2>';
            foreach ($articles as $article) {
                $artTitle = $article->translate('title', $locale) ?: $article->title;
                $artUrl = url('/articles/' . $article->id);
                $html .= '<div style="margin-bottom: 10px;">';
                $html .= '<h3><a href="' . $artUrl . '">' . htmlspecialchars($artTitle) . '</a></h3>';
                $html .= '</div>';
            }
            $html .= '</section>';
        }

        $html .= '</div>';
        return $html;
    }
    
    private function getServicePageMetaTags(string $page, string $locale, string $requestPath = null): array
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
                'uk' => ['title' => 'Оплата та повернення - Account Arena', 'desc' => 'Інформація про способи оплаты та політику повернення коштів.']
            ],
            'contacts' => [
                'ru' => ['title' => 'Контакты - Account Arena', 'desc' => 'Свяжитесь с нами для получения поддержки или по вопросам сотрудничества.'],
                'en' => ['title' => 'Contacts - Account Arena', 'desc' => 'Contact us for support or cooperation inquiries.'],
                'uk' => ['title' => 'Контакти - Account Arena', 'desc' => 'Зв\'яжіться з нами для отримання підтримки або з питань співпраці.']
            ]
        ];

        $pageData = $data[$page][$locale] ?? $data[$page]['ru'];

        // Generate Content
        $htmlContent = $this->generatePageContent($pageData['title'], $pageData['desc']);

        return [
            'title' => $pageData['title'],
            'h1' => $pageData['title'],
            'description' => $pageData['desc'],
            'og:title' => $pageData['title'],
            'og:description' => $pageData['desc'],
            'canonical' => rtrim(url("/" . ($requestPath ?: $page)), '/'),
            'html_content' => $htmlContent
        ];
    }

    private function getDynamicPageMetaTags(string $slug, string $locale): array
    {
        try {
            $page = Page::where('slug', $slug)->where('is_active', true)->first();
            if (!$page) {
                return [];
            }

            $title = $page->translate('meta_title', $locale) ?: $page->translate('title', $locale);
            $desc = $page->translate('meta_description', $locale) ?: $this->smartTruncate(strip_tags($page->translate('content', $locale)), 160);

            // Generate Page Content
            $htmlContent = $this->generatePageContent($title, $page->translate('content', $locale));

            return [
                'title' => ($title ?: 'Page') . ' - Account Arena',
                'h1' => $page->translate('title', $locale),
                'description' => $desc,
                'og:title' => $title,
                'og:description' => $desc,
                'canonical' => rtrim(url("/" . $slug), '/'),
                'html_content' => $htmlContent
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getInfoPageMetaTags(string $page, string $locale): array
    {
        $data = [
            'faq' => [
                'ru' => ['title' => 'FAQ - Часто задаваемые вопросы - Account Arena', 'desc' => 'Ответы на популярные вопросы о покупке аккаунтов, безопасности и гарантиях на Account Arena.'],
                'en' => ['title' => 'FAQ - Frequently Asked Questions - Account Arena', 'desc' => 'Answers to popular questions about buying accounts, security and guarantees on Account Arena.'],
                'uk' => ['title' => 'FAQ - Часті запитання - Account Arena', 'desc' => 'Відповіді на популярні запитання про купівлю акаунтів, безпеку та гарантії на Account Arena.']
            ],
            'guarantees' => [
                'ru' => ['title' => 'Гарантии и безопасность - Account Arena', 'desc' => 'Узнайте о наших гарантиях качества, безопасной сделке и политике защиты покупателей.'],
                'en' => ['title' => 'Guarantees and Security - Account Arena', 'desc' => 'Learn about our quality guarantees, secure deal and buyer protection policy.'],
                'uk' => ['title' => 'Гарантії та безпека - Account Arena', 'desc' => 'Дізнайтеся про наші гарантії якості, безпечну угоду та політику захисту покупців.']
            ],
            'cookies' => [
                'ru' => ['title' => 'Политика использования файлов cookie - Account Arena', 'desc' => 'Информация об использовании файлов cookie на сайте Account Arena.'],
                'en' => ['title' => 'Cookie Policy - Account Arena', 'desc' => 'Information about cookie usage on Account Arena website.'],
                'uk' => ['title' => 'Політика використання файлів cookie - Account Arena', 'desc' => 'Інформація про використання файлів cookie на сайті Account Arena.']
            ],
            'terms' => [
                'ru' => ['title' => 'Пользовательское соглашение - Account Arena', 'desc' => 'Правила использования сервиса Account Arena, права и обязанности сторон.'],
                'en' => ['title' => 'Terms of Service - Account Arena', 'desc' => 'Rules of using Account Arena service, rights and obligations of parties.'],
                'uk' => ['title' => 'Угода користувача - Account Arena', 'desc' => 'Правила використання сервісу Account Arena, права та обов\'язки сторін.']
            ],
            'privacy' => [
                'ru' => ['title' => 'Политика конфиденциальности - Account Arena', 'desc' => 'Как мы собираем, используем и защищаем ваши персональные данные.'],
                'en' => ['title' => 'Privacy Policy - Account Arena', 'desc' => 'How we collect, use and protect your personal data.'],
                'uk' => ['title' => 'Політика конфіденційності - Account Arena', 'desc' => 'Як ми збираємо, використовуємо та захищаємо ваші персональні дані.']
            ]
        ];

        $pageData = $data[$page][$locale] ?? $data[$page]['ru'];
        
        // Generate Content (Stub for now, as these are usually static Vue files or simple content)
        $htmlContent = $this->generatePageContent($pageData['title'], $pageData['desc']);

        $meta = [
            'title' => $pageData['title'],
            'h1' => $pageData['title'],
            'description' => $pageData['desc'],
            'og:title' => $pageData['title'],
            'og:description' => $pageData['desc'],
            'canonical' => rtrim(url("/" . $page), '/'),
            'html_content' => $htmlContent
        ];

        // Add FAQ Schema for FAQ page
        if ($page === 'faq') {
            $faqItems = [];
            
            if ($locale === 'ru') {
                $faqItems = [
                    [
                        'question' => 'Как купить аккаунт?',
                        'answer' => 'Выберите нужный товар, нажмите кнопку "Купить", выберите удобный способ оплаты и следуйте инструкциям. Данные от аккаунта придут моментально после оплаты.'
                    ],
                    [
                        'question' => 'Есть ли гарантия на аккаунты?',
                        'answer' => 'Да, мы предоставляем гарантию на валидность аккаунтов на момент покупки. Подробные условия гарантии указаны на странице "Условия замены".'
                    ],
                    [
                        'question' => 'Какие способы оплаты доступны?',
                        'answer' => 'Мы принимаем оплату через криптовалюту, банковские карты и электронные кошельки. Все платежи защищены и проходят через безопасные платежные шлюзы.'
                    ],
                    [
                        'question' => 'Как быстро я получу аккаунт после оплаты?',
                        'answer' => 'Для товаров с автоматической выдачей - моментально после подтверждения оплаты. Для товаров с ручной выдачей - в течение 1-24 часов в зависимости от времени суток.'
                    ],
                    [
                        'question' => 'Что делать, если аккаунт не работает?',
                        'answer' => 'Свяжитесь с нашей службой поддержки через Telegram или форму обратной связи. Мы проверим проблему и предоставим замену согласно условиям гарантии.'
                    ],
                    [
                        'question' => 'Можно ли вернуть деньги?',
                        'answer' => 'Возврат средств возможен только в случае, если товар не соответствует описанию или не был предоставлен. Подробности в разделе "Оплата и возврат".'
                    ]
                ];
            } elseif ($locale === 'uk') {
                $faqItems = [
                    [
                        'question' => 'Як купити акаунт?',
                        'answer' => 'Оберіть потрібний товар, натисніть кнопку "Купити", оберіть зручний спосіб оплати та дотримуйтесь інструкцій. Дані від акаунту прийдуть миттєво після оплати.'
                    ],
                    [
                        'question' => 'Чи є гарантія на акаунти?',
                        'answer' => 'Так, ми надаємо гарантію на валідність акаунтів на момент покупки. Детальні умови гарантії вказані на сторінці "Умови заміни".'
                    ]
                ];
            } else { // en
                $faqItems = [
                    [
                        'question' => 'How to buy an account?',
                        'answer' => 'Choose the desired product, click "Buy", select a convenient payment method and follow the instructions. Account data will arrive instantly after payment.'
                    ],
                    [
                        'question' => 'Is there a warranty on accounts?',
                        'answer' => 'Yes, we provide a warranty for account validity at the time of purchase. Detailed warranty conditions are listed on the "Replacement Conditions" page.'
                    ]
                ];
            }
            
            $schemaItems = [];
            foreach ($faqItems as $item) {
                $schemaItems[] = [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer']
                    ]
                ];
            }
            
            $meta['schema'] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $schemaItems
            ];
        }

        return $meta;
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

        $title = $titles[$locale] ?? $titles['ru'];
        $description = $descriptions[$locale] ?? $descriptions['ru'];

        // Generate Content (Recent articles list)
        $articles = Article::where('status', 'published')->orderBy('created_at', 'desc')->limit(20)->get();
        $htmlContent = $this->generateArticlesListContent($title, $description, $articles, $locale);

        return [
            'title' => $title,
            'h1' => $title,
            'description' => $description,
            'canonical' => rtrim(url('/articles'), '/'),
            'html_content' => $htmlContent
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
        
        // Canonical (единый стандарт: без слэша в конце, с учетом пагинации)
        if (isset($metaTags['canonical'])) {
            $canonicalUrl = rtrim($metaTags['canonical'], '/'); // Убираем слэш в конце
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
        
        // Hreflang - КРИТИЧЕСКИ ВАЖНО: используем canonical URL вместо текущего запроса
        $locales = ['ru', 'en', 'uk'];
        
        $cleanUrl = isset($metaTags['canonical']) 
            ? $metaTags['canonical']
            : rtrim(preg_replace('/[?&]lang=[^&]*/', '', request()->fullUrl()), '?&');
        
        $hasQuery = str_contains($cleanUrl, '?');

        foreach ($locales as $loc) {
            $separator = $hasQuery ? '&' : '?';
            $langUrl = $cleanUrl . $separator . 'lang=' . $loc;
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

        // --- Вставка HTML-контента (SEO Content Injection) ---
        // Если передан готовый HTML контент (карточка товара, статья),
        // вставляем его внутрь div id="app", чтобы бот видел текст.
        // Vue при инициализации заменит этот контент, но бот успеет его считать.
        if (isset($metaTags['html_content'])) {
            $content = $metaTags['html_content'];
            // Ищем <div id="app">...</div> и вставляем контент внутрь
            // Используем preg_replace, чтобы найти тег со всеми атрибутами
            $html = preg_replace('/(<div id="app"[^>]*>)(<\/div>)/i', '$1' . $content . '$2', $html);
        }
        
        // Вставка H1 в BODY (скрытый для SEO) - только если это не главная страница
        // На главной H1 уже есть в HeroSection.vue, чтобы избежать дублей
        $isHomePage = request()->path() === '' || request()->path() === '/';
        if (isset($metaTags['h1']) && !$isHomePage) {
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

    /**
     * Smart truncation that respects word boundaries
     */
    private function smartTruncate(string $text, int $limit = 160): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $text = mb_substr($text, 0, $limit);
        // Find last space to avoid cutting words
        $lastSpace = mb_strrpos($text, ' ');
        
        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }
        
        return $text . '...';
    }

    /**
     * Helper to generate SEO content for Products (Server-Side Injection)
     */
    private function generateProductContent($product, $title, $description, $locale)
    {
        $price = $product->price . ' USD'; // Simplified currency
        $sku = $product->sku ?: $product->id;
        $img = $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : url('/img/logo_trans.webp');
        
        // Semantic HTML for crawlers inside #app
        // We use inline styles to ensure it doesn't break layout before Vue hydration removes/replaces it.
        // Or we rely on Vue replacing '#app' content entirely.
        
        $html = '<div class="product-seo-content" style="padding: 20px;">';
        $html .= '<article itemscope itemtype="https://schema.org/Product">';
        
        // H1 Title
        $html .= '<h1 itemprop="name" style="font-size: 2em; margin-bottom: 10px;">' . htmlspecialchars($title) . '</h1>';
        
        // Main Image
        $html .= '<div class="product-image" style="margin-bottom: 20px;">';
        $html .= '<img itemprop="image" src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($title) . '" style="max-width: 100%; height: auto;">';
        $html .= '</div>';
        
        // Price & Offer
        $html .= '<div itemprop="offers" itemscope itemtype="https://schema.org/Offer" style="margin-bottom: 20px; font-size: 1.5em; font-weight: bold;">';
        $html .= 'Price: <span itemprop="price">' . $product->price . '</span> ';
        $html .= '<span itemprop="priceCurrency">USD</span>';
        $html .= '<link itemprop="availability" href="https://schema.org/InStock" />';
        $html .= '</div>';
        
        // SKU
        $html .= '<div class="sku" style="margin-bottom: 15px; color: #666;">SKU: <span itemprop="sku">' . htmlspecialchars($sku) . '</span></div>';
        
        // Description
        if ($description) {
            $html .= '<div itemprop="description" style="line-height: 1.6;">' . $description . '</div>';
        }
        
        $html .= '</article>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Helper to generate SEO content for Categories
     */
    private function generateCategoryContent($title, $description, $locale)
    {
        // Ищем товары этой категории для перелинковки в боте
        // Сначала найдем саму категорию по названию (т.к. метод получает уже готовое название)
        $category = Category::where('is_active', true)
            ->get()
            ->filter(function($c) use ($title, $locale) {
                return ($c->translate('name', $locale) ?: $c->name) === $title;
            })->first();

        $html = '<div class="category-seo-content" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        
        if ($description) {
            $html .= '<div class="description" style="margin-top: 15px; line-height: 1.6;">' . htmlspecialchars($description) . '</div>';
        }

        if ($category) {
            $products = ServiceAccount::where('category_id', $category->id)
                ->where('is_active', true)
                ->where('stock_count', '>', 0)
                ->limit(20)
                ->get();

            if ($products->count() > 0) {
                $html .= '<h2 style="margin-top: 30px;">Доступные товары в категории ' . htmlspecialchars($title) . ':</h2>';
                $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">';
                foreach ($products as $product) {
                    $prodName = $this->getLocalizedField($product, 'title', $locale);
                    $prodUrl = url('/products/' . ($product->slug ?: $product->id));
                    $html .= '<div style="border: 1px solid #eee; padding: 10px;">';
                    $html .= '<strong><a href="' . $prodUrl . '">' . htmlspecialchars($prodName) . '</a></strong><br>';
                    $html .= '<span>' . $product->price . ' USD</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Helper to generate SEO content for Articles
     */
    private function generateArticleContent($title, $content, $date, $image)
    {
        $html = '<div class="article-seo-content" style="padding: 20px;">';
        $html .= '<article itemscope itemtype="https://schema.org/Article">';
        $html .= '<h1 itemprop="headline" style="font-size: 2em; margin-bottom: 20px;">' . htmlspecialchars($title) . '</h1>';
        
        if ($image) {
            $html .= '<div class="article-image" style="margin-bottom: 20px;">';
            $html .= '<img itemprop="image" src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($title) . '" style="max-width: 100%; height: auto;">';
            $html .= '</div>';
        }
        
        $html .= '<div class="meta" style="color: #666; margin-bottom: 20px;">';
        $html .= 'Published: <time itemprop="datePublished" datetime="' . $date . '">' . date('Y-m-d', strtotime($date)) . '</time>';
        $html .= '</div>';
        
        $html .= '<div itemprop="articleBody" style="line-height: 1.8;">' . $content . '</div>';
        $html .= '</article>';

        // Добавляем блок "Читайте также" для ботов
        $recentArticles = Article::where('status', 'published')->orderBy('created_at', 'desc')->limit(3)->get();
        if ($recentArticles->count() > 0) {
            $html .= '<div style="margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px;">';
            $html .= '<h3>Читайте также:</h3>';
            foreach ($recentArticles as $art) {
                $artTitle = $art->admin_name ?: $art->id;
                $artUrl = url('/articles/' . $art->id);
                $html .= '<p><a href="' . $artUrl . '">' . htmlspecialchars($artTitle) . '</a></p>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Генерирует HTML для страницы списка категорий
     */
    private function generateCategoriesListContent($title, $description, $categories, $locale)
    {
        $html = '<div class="categories-list-seo" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p>' . htmlspecialchars($description) . '</p>';
        
        if ($categories->count() > 0) {
            $html .= '<ul style="margin-top: 30px; columns: 2;">';
            foreach ($categories as $cat) {
                $name = $cat->translate('name', $locale) ?: $cat->name;
                $url = url('/categories/' . ($cat->slug ?: $cat->id));
                $html .= '<li><a href="' . $url . '">' . htmlspecialchars($name) . '</a></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Генерирует HTML для страницы списка статей
     */
    private function generateArticlesListContent($title, $description, $articles, $locale)
    {
        $html = '<div class="articles-list-seo" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p>' . htmlspecialchars($description) . '</p>';
        
        if ($articles->count() > 0) {
            $html .= '<div style="margin-top: 30px;">';
            foreach ($articles as $art) {
                $name = $art->translate('title', $locale) ?: $art->title;
                $url = url('/articles/' . $art->id);
                $html .= '<div style="margin-bottom: 20px;">';
                $html .= '<h3><a href="' . $url . '">' . htmlspecialchars($name) . '</a></h3>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Helper to generate SEO content for simple pages (Home, Lists, Info)
     */
    private function generatePageContent($title, $description)
    {
        $html = '<div class="seo-content" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        if ($description) {
            $html .= '<div class="description" style="margin-top: 15px; line-height: 1.6;">' . $description . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
