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
        ])->find($id);
        
        // Если категория не найдена, возвращаем 404
        if (!$category) {
            abort(404);
        }
        
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
        
        // Open Graph изображение
        $ogImage = null;
        if ($category->image_url) {
            $ogImage = $category->image_url;
            if (!str_starts_with($ogImage, 'http')) {
                $ogImage = url($ogImage);
            }
        }
        
        // Hreflang альтернативные URL
        $alternateUrls = $this->getAlternateUrls('seo.category', ['id' => $id]);
        
        // Breadcrumbs
        $breadcrumbs = $this->getBreadcrumbs($category, $locale);
        
        // Структурированные данные
        $structuredData = $this->getCategoryStructuredData($category, $name, $seoText, $locale);
        
        return view('seo.category', compact(
            'category',
            'name',
            'metaTitle',
            'metaDescription',
            'seoText',
            'instruction',
            'items',
            'pageTitle',
            'locale',
            'ogImage',
            'alternateUrls',
            'breadcrumbs',
            'structuredData'
        ));
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
     * Получить breadcrumbs для категории
     */
    private function getBreadcrumbs(Category $category, string $locale): array
    {
        $breadcrumbs = [
            [
                'name' => __('Home', [], $locale),
                'url' => url('/')
            ]
        ];
        
        if ($category->parent) {
            $parent = $category->parent;
            $breadcrumbs[] = [
                'name' => $parent->translate('name', $locale),
                'url' => route('seo.category', ['id' => $parent->id])
            ];
        }
        
        $breadcrumbs[] = [
            'name' => $category->translate('name', $locale),
            'url' => url()->current()
        ];
        
        return $breadcrumbs;
    }
    
    /**
     * Получить структурированные данные для категории (Schema.org)
     */
    private function getCategoryStructuredData(Category $category, string $name, ?string $seoText, string $locale): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'description' => Str::limit(strip_tags($seoText ?? ''), 160)
        ];
        
        if ($category->image_url) {
            $imageUrl = $category->image_url;
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = url($imageUrl);
            }
            $data['image'] = $imageUrl;
        }
        
        return $data;
    }
}
