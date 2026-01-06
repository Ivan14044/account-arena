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
        $metaDescription = $this->getLocalizedField($product, 'meta_description', $locale) ?? 
            Str::limit(strip_tags($description), 160);
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
        $pageTitle = $metaTitle ?: ($title . ' - ' . config('app.name'));
        
        return view('seo.product', compact(
            'product',
            'title',
            'description',
            'metaTitle',
            'metaDescription',
            'seoText',
            'instruction',
            'pageTitle',
            'locale'
        ));
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
