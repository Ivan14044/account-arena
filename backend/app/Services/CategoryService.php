<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * Create or update a category
     */
    public function saveCategory(array $data, array $translations, Category $category = null): Category
    {
        return DB::transaction(function () use ($data, $translations, $category) {
            if ($category) {
                $category->update($data);
            } else {
                $category = Category::create($data);
            }

            $category->saveTranslation($translations);

            return $category;
        });
    }

    /**
     * Delete a category
     */
    public function deleteCategory(Category $category): array
    {
        return DB::transaction(function () use ($category) {
            // ПРОВЕРКА: Если есть привязанные товары, запрещаем удаление
            $productsCount = $category->products()->count();
            if ($productsCount > 0) {
                return [
                    'success' => false,
                    'message' => "Невозможно удалить категорию, так как к ней привязано {$productsCount} товаров. Сначала перенесите товары в другую категорию."
                ];
            }

            // ПРОВЕРКА: Если есть подкатегории, также запрещаем
            if ($category->children()->count() > 0) {
                return [
                    'success' => false,
                    'message' => "Невозможно удалить категорию, так как у нее есть подкатегории. Сначала удалите или переместите их."
                ];
            }

            // Удаляем изображение категории, если оно существует
            if ($category->image_url) {
                $imagePath = $category->getRawOriginal('image_url');
                if ($imagePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
                }
            }
            
            // Unlink products (на всякий случай, хотя мы проверили выше)
            $category->products()->update(['category_id' => null]);
            
            $category->delete();

            return [
                'success' => true,
                'message' => 'Категория успешно удалена.'
            ];
        });
    }

    /**
     * Get categories by type with translations and children
     */
    public function getCategories(string $type = null): Collection
    {
        $cacheKey = 'categories_tree_' . ($type ?? 'all');
        
        return Cache::remember($cacheKey, 3600, function () use ($type) {
            $query = Category::with(['translations', 'children.translations']);

            if ($type === Category::TYPE_PRODUCT) {
                $query->productCategories()->parentCategories();
            } elseif ($type === Category::TYPE_ARTICLE) {
                $query->articleCategories();
            }

            return $query->get();
        });
    }

    /**
     * Get subcategories by parent ID
     */
    public function getSubcategories(int $parentId): Collection
    {
        $cacheKey = "subcategories_of_{$parentId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($parentId) {
            return Category::where('parent_id', $parentId)
                ->where('type', Category::TYPE_PRODUCT)
                ->with('translations')
                ->get();
        });
    }

    /**
     * Get category with all related data for SEO/Public view
     */
    public function getCategoryForPublic(int $id, string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        return Category::with(['translations', 'children.translations', 'parent.translations'])
            ->findOrFail($id);
    }

    /**
     * Get breadcrumbs for a category
     */
    public function getBreadcrumbs(Category $category, string $locale): array
    {
        $breadcrumbs = [
            [
                'name' => __('Home', [], $locale),
                'url' => url('/')
            ]
        ];

        $parents = [];
        $parent = $category->parent;
        while ($parent) {
            array_unshift($parents, $parent);
            $parent = $parent->parent;
        }

        foreach ($parents as $p) {
            $breadcrumbs[] = [
                'name' => $p->translate('name', $locale),
                'url' => route('seo.category', ['id' => $p->id])
            ];
        }

        $breadcrumbs[] = [
            'name' => $category->translate('name', $locale),
            'url' => url()->current()
        ];

        return $breadcrumbs;
    }
}
