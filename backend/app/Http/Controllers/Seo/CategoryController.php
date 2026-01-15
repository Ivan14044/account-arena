<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Показать страницу категории с SEO-контентом
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        
        try {
            $category = $this->categoryService->getCategoryForPublic($id, $locale);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404);
        }
        
        // Получаем переводы для текущей локали
        $name = $category->translate('name', $locale);
        if (empty($name)) {
            $name = 'Category ' . $id;
        }
        
        $metaTitle = $category->translate('meta_title', $locale) ?? $name;
        $metaDescription = $category->translate('meta_description', $locale);
        
        // Очищаем дублирование слова "accounts" если оно есть в сохраненном описании
        if ($metaDescription) {
            $metaDescription = preg_replace('/\b(accounts|аккаунты|акаунти)\s+\1\b/iu', '$1', $metaDescription);
        }
        
        // Генерируем осмысленное описание, если оно пустое или слишком короткое
        if ($this->isDescriptionTooShort($metaDescription)) {
            $metaDescription = $this->getCategoryDescription($name, $locale);
        }
        
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
        $pageTitle = $metaTitle ?: ($name . ' - Account Arena');
        
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
        $breadcrumbs = $this->categoryService->getBreadcrumbs($category, $locale);
        
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
        $url = $baseUrl . route($routeName, $params, false);
        
        foreach ($locales as $loc) {
            $alternateUrls[$loc] = $url . '?lang=' . $loc;
        }
        
        return $alternateUrls;
    }
    
    /**
     * Получить структурированные данные для категории (Schema.org)
     */
    private function getCategoryStructuredData(Category $category, string $name, ?string $seoText, string $locale): array
    {
        $description = Str::limit(strip_tags($seoText ?? ''), 160);
        if ($this->isDescriptionTooShort($description)) {
            $description = $this->getCategoryDescription($name, $locale);
        }
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'description' => $description
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
}
