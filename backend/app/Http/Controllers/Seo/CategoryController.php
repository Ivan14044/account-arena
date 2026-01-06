<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Показать страницу категории с SEO-контентом
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        
        $category = Category::with([
            'translations',
            'parent.translations',
            'children.translations'
        ])->findOrFail($id);
        
        // Получаем переводы для текущей локали
        $name = $category->translate('name', $locale);
        $metaTitle = $category->translate('meta_title', $locale) ?? $name;
        $metaDescription = $category->translate('meta_description', $locale);
        $seoText = $category->translate('text', $locale);
        $instruction = $category->translate('instruction', $locale);
        
        // Если нет SEO текста, используем описание
        if (empty($seoText)) {
            $seoText = $metaDescription;
        }
        
        // Загружаем товары/статьи категории для отображения
        $items = [];
        if ($category->type === Category::TYPE_PRODUCT) {
            $items = $category->products()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(20)
                ->get();
        } else {
            $items = $category->articles()
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        }
        
        // Формируем уникальный title (не дублирует H1)
        $pageTitle = $metaTitle ?: ($name . ' - ' . config('app.name'));
        
        return view('seo.category', compact(
            'category',
            'name',
            'metaTitle',
            'metaDescription',
            'seoText',
            'instruction',
            'items',
            'pageTitle',
            'locale'
        ));
    }
}
