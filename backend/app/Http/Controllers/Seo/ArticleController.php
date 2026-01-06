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
        
        return view('seo.article', compact(
            'article',
            'title',
            'content',
            'metaTitle',
            'metaDescription',
            'seoText',
            'pageTitle',
            'locale'
        ));
    }
}
