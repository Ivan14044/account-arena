<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServicePageController extends Controller
{
    /**
     * Маппинг slug страниц на их идентификаторы
     */
    private function getPageSlugMap(): array
    {
        return [
            'suppliers' => 'become-supplier',
            'become-supplier' => 'become-supplier',
            'replace-conditions' => 'conditions',
            'conditions' => 'conditions',
            'payment-refund' => 'payment-refund',
            'contacts' => 'contacts',
        ];
    }

    /**
     * Получить страницу по slug
     */
    private function getPageBySlug(string $slug): ?Page
    {
        $slugMap = $this->getPageSlugMap();
        $actualSlug = $slugMap[$slug] ?? $slug;
        
        return Page::where('slug', $actualSlug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Получить локализованное поле страницы
     */
    private function getLocalizedField(Page $page, string $field, string $locale): ?string
    {
        return $page->translate($field, $locale);
    }

    /**
     * Получить альтернативные URL для hreflang
     */
    private function getAlternateUrls(string $routeName, array $params = []): array
    {
        $locales = ['ru', 'en', 'uk'];
        $urls = [];
        
        foreach ($locales as $locale) {
            $urls[$locale] = route($routeName, array_merge($params, ['lang' => $locale]));
        }
        
        return $urls;
    }

    /**
     * Общий метод для отображения сервисной страницы
     */
    private function showServicePage(string $slug, string $routeName): \Illuminate\View\View
    {
        $locale = app()->getLocale();
        
        $page = $this->getPageBySlug($slug);
        
        if (!$page) {
            abort(404);
        }
        
        // Получаем локализованные поля
        $title = $this->getLocalizedField($page, 'title', $locale);
        $content = $this->getLocalizedField($page, 'content', $locale);
        $metaTitle = $this->getLocalizedField($page, 'meta_title', $locale) ?? $title;
        $metaDescription = $this->getLocalizedField($page, 'meta_description', $locale) ?? 
            Str::limit(strip_tags($content ?: $title), 160);
        
        // Формируем уникальный title
        $pageTitle = $metaTitle ?: ($title . ' - Account Arena');
        
        // Hreflang альтернативные URL
        $alternateUrls = $this->getAlternateUrls($routeName);
        
        // SPA версия для пользователей (alternate)
        $spaUrl = url('/' . $slug);
        
        // Структурированные данные (WebPage Schema)
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $metaDescription,
            'url' => url()->current(),
            'inLanguage' => $locale,
        ];
        
        return view('seo.service-page', compact(
            'page',
            'title',
            'content',
            'metaTitle',
            'metaDescription',
            'pageTitle',
            'locale',
            'alternateUrls',
            'structuredData',
            'spaUrl'
        ));
    }

    /**
     * Показать страницу "Поставщикам"
     */
    public function suppliers(Request $request)
    {
        return $this->showServicePage('suppliers', 'seo.suppliers');
    }

    /**
     * Показать страницу "Условия замены"
     */
    public function replaceConditions(Request $request)
    {
        return $this->showServicePage('replace-conditions', 'seo.replace-conditions');
    }

    /**
     * Показать страницу "Оплата и возврат"
     */
    public function paymentRefund(Request $request)
    {
        return $this->showServicePage('payment-refund', 'seo.payment-refund');
    }
}
