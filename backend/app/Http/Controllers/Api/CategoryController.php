<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type'); // 'product' or 'article'
        
        $query = Category::with(['translations', 'children.translations']);
        
        if ($type === 'product') {
            // Для товаров возвращаем только родительские категории с подкатегориями
            $query->productCategories()->parentCategories();
        } elseif ($type === 'article') {
            $query->articleCategories();
        }

        $categories = $query->get();

        $data = $categories->map(function ($category) {
            $translations = $category->translations
                ->groupBy('locale')
                ->map(fn($translations) => $translations->pluck('value', 'code'));
            
            // Get localized name for current locale or first available
            $locale = app()->getLocale();
            $name = $translations[$locale]['name'] ?? $translations[array_key_first($translations->toArray())]['name'] ?? null;

            // Обрабатываем подкатегории
            $subcategories = $category->children->map(function ($subcategory) use ($locale) {
                $subTranslations = $subcategory->translations
                    ->groupBy('locale')
                    ->map(fn($translations) => $translations->pluck('value', 'code'));
                
                $subName = $subTranslations[$locale]['name'] ?? $subTranslations[array_key_first($subTranslations->toArray())]['name'] ?? null;

                return [
                    'id' => $subcategory->id,
                    'name' => $subName,
                    'translations' => $subTranslations,
                ];
            });

            return [
                'id' => $category->id,
                'type' => $category->type,
                'image_url' => $category->image_url,
                'name' => $name,
                'translations' => $translations,
                'subcategories' => $subcategories,
            ];
        });

        return response()->json($data);
    }

    /**
     * Получить подкатегории по родительской категории
     */
    public function getSubcategories(Request $request, $categoryId)
    {
        $category = Category::where('id', $categoryId)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNull('parent_id')
            ->firstOrFail();

        $subcategories = Category::where('parent_id', $categoryId)
            ->where('type', Category::TYPE_PRODUCT)
            ->with('translations')
            ->get();

        $data = $subcategories->map(function ($subcategory) {
            $translations = $subcategory->translations
                ->groupBy('locale')
                ->map(fn($translations) => $translations->pluck('value', 'code'));
            
            $locale = app()->getLocale();
            $name = $translations[$locale]['name'] ?? $translations[array_key_first($translations->toArray())]['name'] ?? null;

            return [
                'id' => $subcategory->id,
                'name' => $name,
                'translations' => $translations,
            ];
        });

        return response()->json($data);
    }
}
