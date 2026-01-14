<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer для автоматической очистки кеша категорий
 */
class CategoryObserver
{
    /**
     * Очистить кеш категорий
     */
    private function clearCategoriesCache(): void
    {
        Cache::forget('categories_list');
        Cache::forget('categories_tree_all');
        Cache::forget('categories_tree_product');
        Cache::forget('categories_tree_article');
        
        // Также очищаем кеш товаров, так как изменение категории может повлиять на счетчики в каталоге
        Cache::forget('active_accounts_list');
        Cache::forget('active_accounts_list_v2');
        Cache::forget('active_accounts_list_v3');
        
        Log::info('Categories and dependent accounts cache cleared');
    }

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->clearCategoriesCache();
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->clearCategoriesCache();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->clearCategoriesCache();
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->clearCategoriesCache();
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->clearCategoriesCache();
    }
}



