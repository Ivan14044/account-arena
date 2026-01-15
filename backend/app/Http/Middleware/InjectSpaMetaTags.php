<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ServiceAccount;
use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class InjectSpaMetaTags
{
    /**
     * Handle an incoming request.
     * Инжектирует мета-теги в HTML для SPA-роутов
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Проверяем, что это HTML ответ и это SPA-роут
        if (!$this->isSpaRoute($request) || !$this->isHtmlResponse($response)) {
            return $response;
        }
        
        $content = $response->getContent();
        
        // Получаем мета-теги для текущего роута
        $metaTags = $this->getMetaTagsForRoute($request);
        
        if (empty($metaTags)) {
            return $response;
        }
        
        // Инжектируем мета-теги в <head>
        $content = $this->injectMetaTags($content, $metaTags);
        
        $response->setContent($content);
        
        return $response;
    }
    
    /**
     * Проверяет, является ли роут SPA-роутом
     */
    private function isSpaRoute(Request $request): bool
    {
        $path = $request->path();
        
        // SPA-роуты, для которых нужны мета-теги
        return preg_match('#^account/[^/]+$#', $path) || 
               preg_match('#^articles/[^/]+$#', $path) ||
               $path === '' || 
               $path === 'articles';
    }
    
    /**
     * Проверяет, является ли ответ HTML
     */
    private function isHtmlResponse(Response $response): bool
    {
        return str_contains($response->headers->get('Content-Type', ''), 'text/html');
    }
    
    /**
     * Получает мета-теги для текущего роута
     */
    private function getMetaTagsForRoute(Request $request): array
    {
        $path = $request->path();
        $locale = app()->getLocale();
        
        // Страница товара
        if (preg_match('#^account/(.+)$#', $path, $matches)) {
            $idOrSku = $matches[1];
            return $this->getProductMetaTags($idOrSku, $locale);
        }
        
        // Страница статьи
        if (preg_match('#^articles/(\d+)$#', $path, $matches)) {
            $id = (int)$matches[1];
            return $this->getArticleMetaTags($id, $locale);
        }
        
        // Главная страница
        if ($path === '' || $path === '/') {
            return $this->getHomeMetaTags($locale);
        }
        
        // Список статей
        if ($path === 'articles') {
            return $this->getArticlesListMetaTags($locale);
        }
        
        return [];
    }
    
    /**
     * Получает мета-теги для товара
     */
    private function getProductMetaTags(string $idOrSku, string $locale): array
    {
        try {
            // Пытаемся найти по ID или SKU
            $product = ServiceAccount::with(['category.translations'])
                ->where('is_active', true)
                ->where(function($query) use ($idOrSku) {
                    $query->where('id', $idOrSku)
                          ->orWhere('sku', $idOrSku);
                })
                ->first();
            
            if (!$product) {
                return [];
            }
            
            $title = $this->getLocalizedField($product, 'title', $locale);
            $description = $this->getLocalizedField($product, 'description', $locale);
            $metaTitle = $this->getLocalizedField($product, 'meta_title', $locale) ?? $title;
            $metaDescription = $this->getLocalizedField($product, 'meta_description', $locale) ?? 
                Str::limit(strip_tags($description), 160);
            
            $ogImage = null;
            if ($product->image_url) {
                $ogImage = $product->image_url;
                if (!str_starts_with($ogImage, 'http')) {
                    $ogImage = url($ogImage);
                }
            }
            
            $canonical = url("/seo/products/{$product->id}");
            
            return [
                'title' => ($metaTitle ?: $title) . ' - ' . config('app.name'),
                'description' => $metaDescription,
                'og:title' => $metaTitle ?: $title,
                'og:description' => $metaDescription,
                'og:type' => 'product',
                'og:image' => $ogImage ?: url('/img/logo_trans.webp'),
                'og:url' => url()->current(),
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $metaTitle ?: $title,
                'twitter:description' => $metaDescription,
                'twitter:image' => $ogImage ?: url('/img/logo_trans.webp'),
                'canonical' => $canonical,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Получает мета-теги для статьи
     */
    private function getArticleMetaTags(int $id, string $locale): array
    {
        try {
            $article = Article::with(['translations', 'categories.translations'])
                ->where('status', 'published')
                ->find($id);
            
            if (!$article) {
                return [];
            }
            
            $title = $article->translate('title', $locale);
            $content = $article->translate('content', $locale);
            $metaTitle = $article->translate('meta_title', $locale) ?? $title;
            $metaDescription = $article->translate('meta_description', $locale) ?? 
                Str::limit(strip_tags($content), 160);
            
            $ogImage = null;
            if ($article->img) {
                $ogImage = Storage::url($article->img);
                if (!str_starts_with($ogImage, 'http')) {
                    $ogImage = url($ogImage);
                }
            }
            
            $canonical = url("/seo/articles/{$id}");
            
            return [
                'title' => ($metaTitle ?: $title) . ' - ' . config('app.name'),
                'description' => $metaDescription,
                'og:title' => $metaTitle ?: $title,
                'og:description' => $metaDescription,
                'og:type' => 'article',
                'og:image' => $ogImage ?: url('/img/logo_trans.webp'),
                'og:url' => url()->current(),
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $metaTitle ?: $title,
                'twitter:description' => $metaDescription,
                'twitter:image' => $ogImage ?: url('/img/logo_trans.webp'),
                'canonical' => $canonical,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Получает мета-теги для главной страницы
     */
    private function getHomeMetaTags(string $locale): array
    {
        return [
            'title' => config('app.name') . ' - Маркетплейс цифровых аккаунтов',
            'description' => 'Купите качественные аккаунты для игр, соцсетей и сервисов. Быстрая доставка, гарантия качества, лучшие цены на рынке.',
            'og:title' => config('app.name'),
            'og:description' => 'Купите качественные аккаунты для игр, соцсетей и сервисов',
            'og:type' => 'website',
            'og:image' => url('/img/logo_trans.webp'),
            'og:url' => url('/'),
            'canonical' => url('/'),
        ];
    }
    
    /**
     * Получает мета-теги для списка статей
     */
    private function getArticlesListMetaTags(string $locale): array
    {
        return [
            'title' => 'Статьи - ' . config('app.name'),
            'description' => 'Читайте полезные статьи и инструкции на Account Arena',
            'og:title' => 'Статьи - ' . config('app.name'),
            'og:description' => 'Читайте полезные статьи и инструкции',
            'og:type' => 'website',
            'og:image' => url('/img/logo_trans.webp'),
            'og:url' => url('/articles'),
            'canonical' => url('/articles'),
        ];
    }
    
    /**
     * Инжектирует мета-теги в HTML
     */
    private function injectMetaTags(string $html, array $metaTags): string
    {
        $tags = [];
        
        // Title
        if (isset($metaTags['title'])) {
            $tags[] = "<title>{$metaTags['title']}</title>";
        }
        
        // Description
        if (isset($metaTags['description'])) {
            $tags[] = '<meta name="description" content="' . htmlspecialchars($metaTags['description'], ENT_QUOTES, 'UTF-8') . '">';
        }
        
        // Open Graph
        foreach (['og:title', 'og:description', 'og:type', 'og:image', 'og:url'] as $property) {
            if (isset($metaTags[$property])) {
                $content = htmlspecialchars($metaTags[$property], ENT_QUOTES, 'UTF-8');
                $tags[] = "<meta property=\"{$property}\" content=\"{$content}\">";
            }
        }
        
        // Twitter Cards
        foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $name) {
            if (isset($metaTags[$name])) {
                $content = htmlspecialchars($metaTags[$name], ENT_QUOTES, 'UTF-8');
                $tags[] = "<meta name=\"{$name}\" content=\"{$content}\">";
            }
        }
        
        // Canonical
        if (isset($metaTags['canonical'])) {
            $tags[] = '<link rel="canonical" href="' . htmlspecialchars($metaTags['canonical'], ENT_QUOTES, 'UTF-8') . '">';
        }
        
        $injectedTags = implode("\n  ", $tags);
        
        // Инжектируем перед закрывающим </head>
        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', "  {$injectedTags}\n</head>", $html);
        } else {
            // Если нет </head>, добавляем перед </body>
            $html = str_replace('</body>', "  {$injectedTags}\n</body>", $html);
        }
        
        return $html;
    }
    
    /**
     * Получает локализованное поле товара
     */
    private function getLocalizedField(ServiceAccount $product, string $field, string $locale): ?string
    {
        switch ($locale) {
            case 'uk':
                $localizedField = $field . '_uk';
                return $product->$localizedField ?: $product->$field;
            case 'en':
                $localizedField = $field . '_en';
                return $product->$localizedField ?: $product->$field;
            case 'ru':
            default:
                return $product->$field;
        }
    }
}
