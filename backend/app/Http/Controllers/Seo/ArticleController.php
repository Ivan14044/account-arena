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
        
        $pageTitle = __('articles.title', [], $locale) . ' - ' . config('app.name');
        $metaDescription = __('articles.description', [], $locale) ?? 
            'Читайте полезные статьи и инструкции на ' . config('app.name');
        
        return view('seo.articles', compact(
            'articles',
            'pageTitle',
            'metaDescription',
            'locale'
        ));
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
        $pageTitle = $metaTitle ?: ($title . ' - ' . config('app.name'));
        
        // Open Graph изображение
        $ogImage = null;
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
            'structuredData'
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
        
        foreach ($locales as $loc) {
            // Генерируем URL без locale параметра (так как роуты не принимают locale)
            $alternateUrls[$loc] = $baseUrl . route($routeName, $params, false);
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
                'name' => config('app.name')
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
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
