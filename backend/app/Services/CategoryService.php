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
    public function deleteCategory(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            // Удаляем изображение категории, если оно существует
            if ($category->image_url) {
                $imagePath = $category->getRawOriginal('image_url');
                if ($imagePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
                }
            }
            
            // Unlink products
            $category->products()->update(['category_id' => null]);
            
            // Delete subcategories recursively if needed (or let DB cascade handle it)
            // Current Model boot handles articles detach and children product unlink
            
            return $category->delete();
        });
    }

    /**
     * Get categories by type with translations and children
     */
    public function getCategories(string $type = null): Collection
    {
        $query = Category::with(['translations', 'children.translations']);

        if ($type === Category::TYPE_PRODUCT) {
            $query->productCategories()->parentCategories();
        } elseif ($type === Category::TYPE_ARTICLE) {
            $query->articleCategories();
        }

        return $query->get();
    }

    /**
     * Get subcategories by parent ID
     */
    public function getSubcategories(int $parentId): Collection
    {
        return Category::where('parent_id', $parentId)
            ->where('type', Category::TYPE_PRODUCT)
            ->with('translations')
            ->get();
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
