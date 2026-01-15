<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Показать страницу товара с SEO-контентом
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        
        $product = ServiceAccount::with(['category.translations'])
            ->where('is_active', true)
            ->findOrFail($id);
        
        // Получаем локализованные поля
        $title = $this->getLocalizedField($product, 'title', $locale);
        $description = $this->getLocalizedField($product, 'description', $locale);
        $metaTitle = $this->getLocalizedField($product, 'meta_title', $locale) ?? $title;
        // Очищаем описание от внешних URL для мета-тегов
        $cleanDescription = $description ? preg_replace('/https?:\/\/\S+/i', '', $description) : '';
        $cleanDescription = trim(preg_replace('/\s+/', ' ', (string)$cleanDescription));
        $metaDescription = $this->getLocalizedField($product, 'meta_description', $locale) ??
            Str::limit(strip_tags($cleanDescription), 160);
        $seoText = $this->getLocalizedField($product, 'seo_text', $locale);
        $instruction = $this->getLocalizedField($product, 'instruction', $locale);
        $additionalDescription = $this->getLocalizedField($product, 'additional_description', $locale);
        
        // Если нет SEO текста, используем описание + дополнительное описание
        if (empty($seoText)) {
            $seoText = $description;
            if ($additionalDescription) {
                $seoText .= "\n\n" . $additionalDescription;
            }
        }
        
        // Формируем уникальный title
        $pageTitle = $metaTitle ?: ($title . ' - Account Arena');
        
        // Open Graph изображение
        $ogImage = null;
        if ($product->image_url) {
            $ogImage = $product->image_url;
            if (!str_starts_with($ogImage, 'http')) {
                $ogImage = url($ogImage);
            }
        }
        
        // Hreflang альтернативные URL
        $alternateUrls = $this->getAlternateUrls('seo.product', ['id' => $id]);
        
        // Breadcrumbs
        $breadcrumbs = $this->getBreadcrumbs($product, $locale, $title);
        
        // Структурированные данные
        $structuredData = $this->getProductStructuredData($product, $title, $cleanDescription, $locale);
        
        // SPA версия для пользователей (alternate)
        $spaUrl = url('/account/' . $id);
        
        return view('seo.product', compact(
            'product',
            'title',
            'description',
            'metaTitle',
            'metaDescription',
            'seoText',
            'instruction',
            'pageTitle',
            'locale',
            'ogImage',
            'alternateUrls',
            'breadcrumbs',
            'structuredData',
            'spaUrl'
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
            // Добавляем параметр языка к URL
            $alternateUrls[$loc] = $url . '?lang=' . $loc;
        }
        
        return $alternateUrls;
    }
    
    /**
     * Получить breadcrumbs для товара
     */
    private function getBreadcrumbs(ServiceAccount $product, string $locale, ?string $title = null): array
    {
        $breadcrumbs = [
            [
                'name' => __('Home', [], $locale),
                'url' => url('/')
            ]
        ];
        
        if ($product->category) {
            $breadcrumbs[] = [
                'name' => $product->category->translate('name', $locale),
                'url' => route('seo.category', ['id' => $product->category->id])
            ];
        }
        
        $breadcrumbs[] = [
            'name' => $title ?? $product->title,
            'url' => url()->current()
        ];
        
        return $breadcrumbs;
    }
    
    /**
     * Получить структурированные данные для товара (Schema.org)
     */
    private function getProductStructuredData(ServiceAccount $product, string $title, ?string $description, string $locale): array
    {
        $descriptionText = Str::limit(strip_tags($description ?? ''), 160);
        if (empty($descriptionText)) {
            $descriptionText = $title . ' - Account Arena';
        }
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $title,
            'description' => $descriptionText,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Account Arena'
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->price ?? 0,
                'priceCurrency' => config('app.currency', 'USD'),
                'availability' => $product->getAvailableStock() > 0 
                    ? 'https://schema.org/InStock' 
                    : 'https://schema.org/OutOfStock',
                'url' => url()->current()
            ]
        ];
        
        if ($product->sku) {
            $data['sku'] = $product->sku;
            $data['additionalProperty'] = [
                [
                    '@type' => 'PropertyValue',
                    'name' => 'SKU',
                    'value' => $product->sku
                ]
            ];
        }
        
        if ($product->image_url) {
            $imageUrl = $product->image_url;
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = url($imageUrl);
            }
            $data['image'] = $imageUrl;
        }
        
        if ($product->category) {
            $data['category'] = $product->category->translate('name', $locale);
        }
        
        return $data;
    }
    
    /**
     * Получить локализованное поле товара
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
