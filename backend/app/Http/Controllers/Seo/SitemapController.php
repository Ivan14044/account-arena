<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Генерация sitemap.xml
     */
    public function index()
    {
        // Кэшируем sitemap на 24 часа
        $sitemap = Cache::remember('sitemap_xml', 60 * 60 * 24, function () {
            $baseUrl = config('app.url');
            $locales = ['ru', 'en', 'uk'];
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
            $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
            
            // Главная страница
            foreach ($locales as $locale) {
                $xml .= $this->generateUrl($baseUrl, 1.0, 'daily', $locale);
            }
            
            // SEO страницы статей
            $articles = Article::where('status', 'published')
                ->orderBy('updated_at', 'desc')
                ->get();
            
            foreach ($articles as $article) {
                foreach ($locales as $locale) {
                    $url = $baseUrl . '/seo/articles/' . $article->id;
                    $lastmod = $article->updated_at->format('Y-m-d');
                    $xml .= $this->generateUrl($url, 0.8, 'weekly', $locale, $lastmod);
                }
            }
            
            // SEO страницы категорий
            $categories = Category::all();
            
            foreach ($categories as $category) {
                foreach ($locales as $locale) {
                    $url = $baseUrl . '/seo/categories/' . $category->id;
                    $xml .= $this->generateUrl($url, 0.7, 'weekly', $locale);
                }
            }
            
            // SEO страницы товаров
            $products = ServiceAccount::where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get();
            
            foreach ($products as $product) {
                foreach ($locales as $locale) {
                    $url = $baseUrl . '/seo/products/' . $product->id;
                    $lastmod = $product->updated_at->format('Y-m-d');
                    $xml .= $this->generateUrl($url, 0.6, 'monthly', $locale, $lastmod);
                }
            }
            
            // Список статей
            foreach ($locales as $locale) {
                $url = $baseUrl . '/seo/articles';
                $xml .= $this->generateUrl($url, 0.7, 'daily', $locale);
            }
            
            $xml .= '</urlset>';
            
            return $xml;
        });
        
        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
    
    /**
     * Генерация одного URL для sitemap
     */
    private function generateUrl(string $url, float $priority, string $changefreq, ?string $locale = null, ?string $lastmod = null): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
        
        if ($lastmod) {
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        
        // Добавляем альтернативные языковые версии
        if ($locale) {
            $locales = ['ru', 'en', 'uk'];
            foreach ($locales as $altLocale) {
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$altLocale}\" href=\"" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "\" />\n";
            }
        }
        
        $xml .= "  </url>\n";
        
        return $xml;
    }
}
