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
        
        $metaTags = $this->getMetaTagsForRoute($request);
        
        if (!empty($metaTags)) {
            $html = $this->injectMetaTags($html, $metaTags);
        }
        
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }
    
    private function getMetaTagsForRoute(Request $request): array
    {
        $path = trim($request->path(), '/');
        $locale = $request->get('lang', app()->getLocale());
        
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
        
        return [];
    }

    private function getProductMetaTags(string $idOrSku, string $locale): array
    {
        try {
            $product = ServiceAccount::where('is_active', true)
                ->where(function($query) use ($idOrSku) {
                    $query->where('id', $idOrSku)->orWhere('sku', $idOrSku);
                })->first();
            
            if (!$product) return [];
            
            $title = $this->getLocalizedField($product, 'title', $locale);
            $desc = $this->getLocalizedField($product, 'meta_description', $locale) ?: Str::limit(strip_tags($this->getLocalizedField($product, 'description', $locale)), 160);
            
            return [
                'title' => ($title ?: 'Product ' . $idOrSku) . ' - Account Arena',
                'h1' => $title,
                'description' => $desc,
                'og:title' => $title,
                'og:description' => $desc,
                'og:type' => 'product',
                'og:image' => $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : url('/img/logo_trans.webp'),
                'canonical' => url("/seo/products/{$product->id}"),
            ];
        } catch (\Exception $e) { return []; }
    }
    
    private function getArticleMetaTags(int $id, string $locale): array
    {
        try {
            $article = Article::where('status', 'published')->find($id);
            if (!$article) return [];
            
            $title = $article->translate('title', $locale);
            $desc = $article->translate('meta_description', $locale) ?: Str::limit(strip_tags($article->translate('content', $locale)), 160);
            
            return [
                'title' => ($title ?: 'Article ' . $id) . ' - Account Arena',
                'h1' => $title,
                'description' => $desc,
                'og:title' => $title,
                'og:description' => $desc,
                'og:type' => 'article',
                'og:image' => $article->img ? url(Storage::url($article->img)) : url('/img/logo_trans.webp'),
                'canonical' => url("/seo/articles/{$id}"),
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
        return [
            'title' => 'Account Arena - Маркетплейс цифровых аккаунтов',
            'h1' => 'Account Arena - Маркетплейс цифровых аккаунтов',
            'description' => 'Купите качественные аккаунты для игр, соцсетей и сервисов. Быстрая доставка, гарантия качества, лучшие цены на рынке.',
            'og:title' => 'Account Arena',
            'og:description' => 'Купите качественные аккаунты для игр, соцсетей и сервисов',
            'canonical' => url('/'),
        ];
    }
    
    private function getArticlesListMetaTags(string $locale): array
    {
        return [
            'title' => 'Статьи - Account Arena',
            'h1' => 'Статьи',
            'description' => 'Читайте полезные статьи и инструкции на Account Arena',
            'canonical' => url('/articles'),
        ];
    }
    
    private function injectMetaTags(string $html, array $metaTags): string
    {
        // 1. Очищаем ВСЕ возможные старые теги, чтобы избежать дублей
        $html = preg_replace('/<title>.*?<\/title>/is', '', $html);
        $html = preg_replace('/<meta name="description".*?>/is', '', $html);
        $html = preg_replace('/<meta property="og:.*?".*?>/is', '', $html);
        $html = preg_replace('/<meta name="twitter:.*?".*?>/is', '', $html);
        $html = preg_replace('/<link rel="canonical".*?>/is', '', $html);
        $html = preg_replace('/<link rel="alternate" hreflang=".*?".*?>/is', '', $html);
        
        $headTags = [];
        
        // Title
        $titleText = $metaTags['title'] ?? 'Account Arena';
        $headTags[] = '<title>' . htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8') . '</title>';
        
        // Description
        if (isset($metaTags['description'])) {
            $headTags[] = '<meta name="description" content="' . htmlspecialchars($metaTags['description'], ENT_QUOTES, 'UTF-8') . '">';
        }
        
        // Canonical
        if (isset($metaTags['canonical'])) {
            $headTags[] = '<link rel="canonical" href="' . htmlspecialchars($metaTags['canonical'], ENT_QUOTES, 'UTF-8') . '">';
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
        foreach ($locales as $loc) {
            $langUrl = $currentUrl . (str_contains($currentUrl, '?') ? '&' : '?') . 'lang=' . $loc;
            $headTags[] = '<link rel="alternate" hreflang="' . $loc . '" href="' . htmlspecialchars($langUrl, ENT_QUOTES, 'UTF-8') . '">';
        }
        $headTags[] = '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') . '">';
        
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
