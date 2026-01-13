<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Нормализация image_url в категориях
        $categories = DB::table('categories')->whereNotNull('image_url')->get();
        foreach ($categories as $category) {
            $url = $category->image_url;
            if (str_starts_with($url, 'http')) {
                // Извлекаем относительный путь из полного URL
                $path = parse_url($url, PHP_URL_PATH);
                if ($path) {
                    $path = ltrim($path, '/');
                    // Убираем 'storage/' если есть, так как Storage::url добавляет его автоматически
                    if (str_starts_with($path, 'storage/')) {
                        $path = substr($path, 8);
                    }
                    DB::table('categories')->where('id', $category->id)->update(['image_url' => $path]);
                }
            } else {
                // Если это уже относительный путь, нормализуем его
                $path = ltrim($url, '/');
                if (str_starts_with($path, 'storage/')) {
                    $path = substr($path, 8);
                }
                if ($path !== $url) {
                    DB::table('categories')->where('id', $category->id)->update(['image_url' => $path]);
                }
            }
        }

        // Нормализация image_url в товарах
        $products = DB::table('service_accounts')->whereNotNull('image_url')->get();
        foreach ($products as $product) {
            $url = $product->image_url;
            if (str_starts_with($url, 'http')) {
                // Извлекаем относительный путь из полного URL
                $path = parse_url($url, PHP_URL_PATH);
                if ($path) {
                    $path = ltrim($path, '/');
                    // Убираем 'storage/' если есть
                    if (str_starts_with($path, 'storage/')) {
                        $path = substr($path, 8);
                    }
                    DB::table('service_accounts')->where('id', $product->id)->update(['image_url' => $path]);
                }
            } else {
                // Если это уже относительный путь, нормализуем его
                $path = ltrim($url, '/');
                if (str_starts_with($path, 'storage/')) {
                    $path = substr($path, 8);
                }
                if ($path !== $url) {
                    DB::table('service_accounts')->where('id', $product->id)->update(['image_url' => $path]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Обратная миграция не требуется, так как аксессоры в моделях обеспечат работу
    }
};
