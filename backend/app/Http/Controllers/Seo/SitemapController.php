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
            // Единый стандарт URL без слэша в конце
            $baseUrl = rtrim(config('app.url'), '/');
            $locales = ['ru', 'en', 'uk'];
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
            $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
            
            // Главная страница (один раз, но с hreflang для всех языков)
            $xml .= $this->generateUrl($baseUrl, 1.0, 'daily', 'ru');
            
            // SEO страницы статей
            $articles = Article::where('status', 'published')
                ->orderBy('updated_at', 'desc')
                ->get();
            
            foreach ($articles as $article) {
                // Point to main SPA Article route
                $url = $baseUrl . '/articles/' . $article->id;
                $lastmod = $article->updated_at->format('Y-m-d');
                $xml .= $this->generateUrl($url, 0.8, 'weekly', 'ru', $lastmod);
            }
            
            // SEO страницы категорий
            $categories = Category::with('translations')->get();
            
            foreach ($categories as $category) {
                // Проверяем, что у категории есть хотя бы одно название
                $hasName = $category->translations()
                    ->where('code', 'name')
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->exists();
                
                if (!$hasName) {
                    continue;
                }
                
                // Point to main SPA Category route
                $url = $baseUrl . '/categories/' . $category->id;
                $xml .= $this->generateUrl($url, 0.7, 'weekly', 'ru');
            }
            
            // SEO страницы товаров
            $products = ServiceAccount::where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get();
            
            foreach ($products as $product) {
                // Point to main SPA Product route
                // Use SKU or ID based on preference, prioritizing ID for compatibility with current routes
                $url = $baseUrl . '/account/' . $product->id;
                $lastmod = $product->updated_at->format('Y-m-d');
                $xml .= $this->generateUrl($url, 0.8, 'daily', 'ru', $lastmod);
            }
            
            // Список статей
            $url = $baseUrl . '/articles';
            $xml .= $this->generateUrl($url, 0.7, 'daily', 'ru');
            
            // Сервисные страницы (SPA версии) - это основные страницы
            $servicePages = [
                'become-supplier' => 0.6, 
                'conditions' => 0.5, 
                'payment-refund' => 0.6, 
                'contacts' => 0.5, 
                // 'suppliers' is usually an alias to become-supplier or list, ensure uniqueness
            ];
            foreach ($servicePages as $page => $priority) {
                $xml .= $this->generateUrl($baseUrl . '/' . $page, $priority, 'monthly', 'ru');
            }
            
            $xml .= '</urlset>';
            
            return $xml;
        });
        
        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('X-Content-Type-Options', 'nosniff');
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
                $altUrl = $url . '?lang=' . $altLocale;
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$altLocale}\" href=\"" . htmlspecialchars($altUrl, ENT_XML1, 'UTF-8') . "\" />\n";
            }
        }
        
        $xml .= "  </url>\n";
        
        return $xml;
    }
}
