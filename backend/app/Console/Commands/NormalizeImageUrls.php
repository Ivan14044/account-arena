<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ServiceAccount;
use App\Models\Banner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class NormalizeImageUrls extends Command
{
    protected $signature = 'images:normalize-urls';
    protected $description = 'Normalize all image URLs in categories, products, and banners to relative paths.';

    public function handle()
    {
        $this->info('Normalizing image URLs...');

        $categoriesFixed = 0;
        $productsFixed = 0;
        $bannersFixed = 0;

        // Нормализация категорий
        $categories = Category::whereNotNull('image_url')->get();
        foreach ($categories as $category) {
            $url = $category->image_url;
            $normalized = $this->normalizeUrl($url);
            
            if ($normalized !== $url) {
                $category->image_url = $normalized;
                $category->save();
                $categoriesFixed++;
                $this->line("Fixed category #{$category->id}: {$url} -> {$normalized}");
            }
        }

        // Нормализация товаров
        $products = ServiceAccount::whereNotNull('image_url')->get();
        foreach ($products as $product) {
            $url = $product->image_url;
            $normalized = $this->normalizeUrl($url);
            
            if ($normalized !== $url) {
                $product->image_url = $normalized;
                $product->save();
                $productsFixed++;
                $this->line("Fixed product #{$product->id}: {$url} -> {$normalized}");
            }
        }

        // Нормализация баннеров
        $banners = Banner::whereNotNull('image_url')->get();
        foreach ($banners as $banner) {
            $url = $banner->image_url;
            $normalized = $this->normalizeUrl($url);
            
            if ($normalized !== $url) {
                $banner->image_url = $normalized;
                $banner->save();
                $bannersFixed++;
                $this->line("Fixed banner #{$banner->id}: {$url} -> {$normalized}");
            }
        }

        $this->info("Fixed {$categoriesFixed} category image URLs.");
        $this->info("Fixed {$productsFixed} product image URLs.");
        $this->info("Fixed {$bannersFixed} banner image URLs.");

        return 0;
    }

    private function normalizeUrl(string $url): string
    {
        // Если это полный URL, извлекаем путь
        if (str_starts_with($url, 'http')) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) {
                $path = ltrim($path, '/');
                // Убираем 'storage/' если есть
                if (str_starts_with($path, 'storage/')) {
                    $path = substr($path, 8);
                }
                return $path;
            }
            return $url;
        }

        // Если это уже относительный путь, нормализуем его
        $path = ltrim($url, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        return $path;
    }
}
