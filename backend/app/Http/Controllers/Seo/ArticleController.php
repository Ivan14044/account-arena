<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    /**
     * Показать список статей с SEO-контентом
     */
    public function index(Request $request)
    {
        $locale = app()->getLocale();
        
        $articles = Article::where('status', 'published')
            ->with(['translations', 'categories.translations'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        
        $pageTitle = __('articles.title', [], $locale) . ' - Account Arena';
        $metaDescription = __('articles.description', [], $locale) ?? 
            'Читайте полезные статьи и инструкции на Account Arena';
        
        // Open Graph изображение (дефолтное или логотип)
        $ogImage = url('/favicon.ico');
        
        // Hreflang альтернативные URL
        $alternateUrls = $this->getAlternateUrls('seo.articles', []);
        
        // Структурированные данные для списка статей
        $structuredData = $this->getArticlesListStructuredData($articles, $locale);
        
        return view('seo.articles', compact(
            'articles',
            'pageTitle',
            'metaDescription',
            'locale',
            'ogImage',
            'alternateUrls',
            'structuredData'
        ));
    }
    
    /**
     * Получить структурированные данные для списка статей (Schema.org)
     */
    private function getArticlesListStructuredData($articles, string $locale): array
    {
        $items = [];
        foreach ($articles as $index => $article) {
            $title = $article->translate('title', $locale);
            $url = route('seo.article', $article->id);
            
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Article',
                    'headline' => $title,
                    'url' => $url,
                    'datePublished' => $article->created_at->toIso8601String(),
                ]
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $items
        ];
    }
    
    /**
     * Показать отдельную статью с SEO-контентом
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        
        $article = Article::with(['translations', 'categories.translations'])
            ->findOrFail($id);
        
        if ($article->status !== 'published') {
            abort(404);
        }
        
        // Получаем переводы
        $title = $article->translate('title', $locale);
        $content = $article->translate('content', $locale);
        $metaTitle = $article->translate('meta_title', $locale) ?? $title;
        $metaDescription = $article->translate('meta_description', $locale) ?? 
            Str::limit(strip_tags($content), 160);
        $short = $article->translate('short', $locale);
        
        // Формируем SEO текст (300-500 слов)
        $seoText = $content;
        if (empty($seoText) && $short) {
            $seoText = $short;
        }
        
        // Формируем уникальный title
        $pageTitle = $metaTitle ?: ($title . ' - Account Arena');
        
        // Open Graph изображение
        $ogImage = null;
        $ogType = 'article';
        if ($article->img) {
            $ogImage = Storage::url($article->img);
            if (!str_starts_with($ogImage, 'http')) {
                $ogImage = url($ogImage);
            }
        }
        
        // Hreflang альтернативные URL
        $alternateUrls = $this->getAlternateUrls('seo.article', ['id' => $id]);
        
        // Структурированные данные для статьи
        $structuredData = $this->getArticleStructuredData($article, $title, $content, $locale);
        
        // SPA версия для пользователей (alternate)
        $spaUrl = url('/articles/' . $id);
        
        return view('seo.article', compact(
            'article',
            'title',
            'content',
            'metaTitle',
            'metaDescription',
            'seoText',
            'pageTitle',
            'locale',
            'ogImage',
            'ogType',
            'alternateUrls',
            'structuredData',
            'spaUrl'
        ))->with('ogType', 'article');
    }
    
    /**
     * Получить альтернативные URL для hreflang
     */
    private function getAlternateUrls(string $routeName, array $params = []): array
    {
        $alternateUrls = [];
        $locales = ['ru', 'en', 'uk'];
        $baseUrl = config('app.url');
        $url = $baseUrl . route($routeName, $params, false);
        
        foreach ($locales as $loc) {
            // Добавляем параметр языка к URL
            $alternateUrls[$loc] = $url . '?lang=' . $loc;
        }
        
        return $alternateUrls;
    }
    
    /**
     * Получить структурированные данные для статьи (Schema.org)
     */
    private function getArticleStructuredData(Article $article, string $title, string $content, string $locale): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'description' => Str::limit(strip_tags($content), 160),
            'datePublished' => $article->created_at->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Account Arena'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Account Arena',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/favicon.ico')
                ]
            ]
        ];
        
        if ($article->img) {
            $imageUrl = Storage::url($article->img);
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = url($imageUrl);
            }
            $data['image'] = $imageUrl;
        }
        
        if ($article->categories->count() > 0) {
            $data['articleSection'] = $article->categories->first()->translate('name', $locale);
        }
        
        return $data;
    }
}
