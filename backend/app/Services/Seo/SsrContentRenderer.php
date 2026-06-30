<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\ServiceAccount;
use App\Support\Seo\SeoText;

/**
 * Renders the server-side SEO body content injected into the SPA shell for crawlers.
 * Extracted verbatim from SpaController (the `generate*Content` helpers); pure HTML
 * builders that read models directly but hold no controller state.
 */
class SsrContentRenderer
{
    public function generateHomeContent($title, $description, $locale)
    {
        $html = '<div class="home-seo-content" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';

        if ($description) {
            $html .= '<p class="description" style="margin-top: 15px; line-height: 1.6;">' . htmlspecialchars($description) . '</p>';
        }

        // 1. Секция категорий (Только родительские товарные категории)
        $categories = Category::productCategories()->parentCategories()->limit(20)->get();
        if ($categories->count() > 0) {
            $catLabel = [
                'ru' => 'Популярные категории аккаунтов:',
                'en' => 'Popular Account Categories:',
                'uk' => 'Популярні категорії акаунтів:'
            ];
            $html .= '<section style="margin-top: 30px;">';
            $html .= '<h2>' . ($catLabel[$locale] ?? $catLabel['ru']) . '</h2>';
            $html .= '<ul style="columns: 2; -webkit-columns: 2; -moz-columns: 2;">';
            foreach ($categories as $cat) {
                $catName = $cat->translate('name', $locale) ?: $cat->name;
                $catUrl = url('/categories/' . ($cat->slug ?: $cat->id));
                $html .= '<li><a href="' . $catUrl . '">' . htmlspecialchars($catName) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</section>';
        }

        // 2. Секция популярных товаров (По просмотрам)
        $products = ServiceAccount::where('is_active', true)
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get();

        if ($products->count() > 0) {
            $prodLabel = [
                'ru' => 'Топ товаров сегодня:',
                'en' => 'Top products today:',
                'uk' => 'Топ товарів сьогодні:'
            ];
            $html .= '<section style="margin-top: 30px;">';
            $html .= '<h2>' . ($prodLabel[$locale] ?? $prodLabel['ru']) . '</h2>';
            $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">';
            foreach ($products as $product) {
                $prodName = SeoText::localized($product, 'title', $locale);
                $prodUrl = url('/products/' . ($product->slug ?: $product->id));
                $html .= '<div style="border: 1px solid #eee; padding: 10px;">';
                $html .= '<strong><a href="' . $prodUrl . '">' . htmlspecialchars($prodName) . '</a></strong><br>';
                $html .= '<span>Price: ' . $product->price . ' USD</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</section>';
        }

        // 3. Последние статьи блога
        $articles = Article::where('status', 'published')->orderBy('created_at', 'desc')->limit(5)->get();
        if ($articles->count() > 0) {
            $artLabel = [
                'ru' => 'Полезные инструкции и новости:',
                'en' => 'Useful instructions and news:',
                'uk' => 'Корисні інструкції та новини:'
            ];
            $html .= '<section style="margin-top: 30px;">';
            $html .= '<h2>' . ($artLabel[$locale] ?? $artLabel['ru']) . '</h2>';
            foreach ($articles as $article) {
                $artTitle = $article->translate('title', $locale) ?: $article->title;
                $artUrl = url('/articles/' . $article->id);
                $html .= '<div style="margin-bottom: 10px;">';
                $html .= '<h3><a href="' . $artUrl . '">' . htmlspecialchars($artTitle) . '</a></h3>';
                $html .= '</div>';
            }
            $html .= '</section>';
        }

        $html .= '</div>';
        return $html;
    }

    public function generateProductContent($product, $title, $description, $locale)
    {
        $price = $product->price . ' USD'; // Simplified currency
        $sku = $product->sku ?: $product->id;
        $img = $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : url('/img/logo_trans.webp');

        // Semantic HTML for crawlers inside #app
        // We use inline styles to ensure it doesn't break layout before Vue hydration removes/replaces it.
        // Or we rely on Vue replacing '#app' content entirely.

        $html = '<div class="product-seo-content" style="padding: 20px;">';
        $html .= '<article itemscope itemtype="https://schema.org/Product">';

        // H1 Title
        $html .= '<h1 itemprop="name" style="font-size: 2em; margin-bottom: 10px;">' . htmlspecialchars($title) . '</h1>';

        // Main Image
        $html .= '<div class="product-image" style="margin-bottom: 20px;">';
        $html .= '<img itemprop="image" src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($title) . '" style="max-width: 100%; height: auto;">';
        $html .= '</div>';

        // Price & Offer
        $html .= '<div itemprop="offers" itemscope itemtype="https://schema.org/Offer" style="margin-bottom: 20px; font-size: 1.5em; font-weight: bold;">';
        $html .= 'Price: <span itemprop="price">' . $product->price . '</span> ';
        $html .= '<span itemprop="priceCurrency">USD</span>';
        $html .= '<link itemprop="availability" href="https://schema.org/InStock" />';
        $html .= '</div>';

        // SKU
        $html .= '<div class="sku" style="margin-bottom: 15px; color: #666;">SKU: <span itemprop="sku">' . htmlspecialchars($sku) . '</span></div>';

        // Description
        if ($description) {
            $html .= '<div itemprop="description" style="line-height: 1.6;">' . $description . '</div>';
        }

        $html .= '</article>';
        $html .= '</div>';

        return $html;
    }

    public function generateCategoryContent($title, $description, $locale)
    {
        // Ищем товары этой категории для перелинковки в боте
        // Сначала найдем саму категорию по названию (т.к. метод получает уже готовое название)
        $category = Category::where('is_active', true)
            ->get()
            ->filter(function ($c) use ($title, $locale) {
                return ($c->translate('name', $locale) ?: $c->name) === $title;
            })->first();

        $html = '<div class="category-seo-content" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';

        if ($description) {
            $html .= '<div class="description" style="margin-top: 15px; line-height: 1.6;">' . htmlspecialchars($description) . '</div>';
        }

        if ($category) {
            $products = ServiceAccount::where('category_id', $category->id)
                ->where('is_active', true)
                ->where('stock_count', '>', 0)
                ->limit(20)
                ->get();

            if ($products->count() > 0) {
                $html .= '<h2 style="margin-top: 30px;">Доступные товары в категории ' . htmlspecialchars($title) . ':</h2>';
                $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">';
                foreach ($products as $product) {
                    $prodName = SeoText::localized($product, 'title', $locale);
                    $prodUrl = url('/products/' . ($product->slug ?: $product->id));
                    $html .= '<div style="border: 1px solid #eee; padding: 10px;">';
                    $html .= '<strong><a href="' . $prodUrl . '">' . htmlspecialchars($prodName) . '</a></strong><br>';
                    $html .= '<span>' . $product->price . ' USD</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    public function generateArticleContent($title, $content, $date, $image)
    {
        $html = '<div class="article-seo-content" style="padding: 20px;">';
        $html .= '<article itemscope itemtype="https://schema.org/Article">';
        $html .= '<h1 itemprop="headline" style="font-size: 2em; margin-bottom: 20px;">' . htmlspecialchars($title) . '</h1>';

        if ($image) {
            $html .= '<div class="article-image" style="margin-bottom: 20px;">';
            $html .= '<img itemprop="image" src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($title) . '" style="max-width: 100%; height: auto;">';
            $html .= '</div>';
        }

        $html .= '<div class="meta" style="color: #666; margin-bottom: 20px;">';
        $html .= 'Published: <time itemprop="datePublished" datetime="' . $date . '">' . date('Y-m-d', strtotime($date)) . '</time>';
        $html .= '</div>';

        $html .= '<div itemprop="articleBody" style="line-height: 1.8;">' . $content . '</div>';
        $html .= '</article>';

        // Добавляем блок "Читайте также" для ботов
        $recentArticles = Article::where('status', 'published')->orderBy('created_at', 'desc')->limit(3)->get();
        if ($recentArticles->count() > 0) {
            $html .= '<div style="margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px;">';
            $html .= '<h3>Читайте также:</h3>';
            foreach ($recentArticles as $art) {
                $artTitle = $art->admin_name ?: $art->id;
                $artUrl = url('/articles/' . $art->id);
                $html .= '<p><a href="' . $artUrl . '">' . htmlspecialchars($artTitle) . '</a></p>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public function generateCategoriesListContent($title, $description, $categories, $locale)
    {
        $html = '<div class="categories-list-seo" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p>' . htmlspecialchars($description) . '</p>';

        if ($categories->count() > 0) {
            $html .= '<ul style="margin-top: 30px; columns: 2;">';
            foreach ($categories as $cat) {
                $name = $cat->translate('name', $locale) ?: $cat->name;
                $url = url('/categories/' . ($cat->slug ?: $cat->id));
                $html .= '<li><a href="' . $url . '">' . htmlspecialchars($name) . '</a></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';
        return $html;
    }

    public function generateArticlesListContent($title, $description, $articles, $locale)
    {
        $html = '<div class="articles-list-seo" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p>' . htmlspecialchars($description) . '</p>';

        if ($articles->count() > 0) {
            $html .= '<div style="margin-top: 30px;">';
            foreach ($articles as $art) {
                $name = $art->translate('title', $locale) ?: $art->title;
                $url = url('/articles/' . $art->id);
                $html .= '<div style="margin-bottom: 20px;">';
                $html .= '<h3><a href="' . $url . '">' . htmlspecialchars($name) . '</a></h3>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function generatePageContent($title, $description)
    {
        $html = '<div class="seo-content" style="padding: 20px;">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        if ($description) {
            $html .= '<div class="description" style="margin-top: 15px; line-height: 1.6;">' . $description . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
