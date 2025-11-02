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
        
        $query = Category::with('translations');
        
        if ($type === 'product') {
            $query->productCategories();
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

            return [
                'id' => $category->id,
                'type' => $category->type,
                'image_url' => $category->image_url,
                'name' => $name,
                'translations' => $translations,
            ];
        });

        return response()->json($data);
    }
}
