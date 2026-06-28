<?php

namespace Tests\Feature;

use App\Models\ServiceAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Регрессионный тест для FIX (M1): кеш каталога active_accounts_list_v4
 * должен сбрасываться при изменении товаров (раньше чистились только
 * устаревшие _v1.._v3 → стейл-сток/цена/видимость до 5 минут).
 */
class CatalogCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_product_clears_v4_catalog_cache(): void
    {
        Cache::put('active_accounts_list_v4', ['stale'], 300);

        ServiceAccount::factory()->create();

        $this->assertNull(Cache::get('active_accounts_list_v4'));
    }

    public function test_updating_product_clears_v4_catalog_cache(): void
    {
        $product = ServiceAccount::factory()->create();
        Cache::put('active_accounts_list_v4', ['stale'], 300);

        $product->update(['price' => 999.99]);

        $this->assertNull(Cache::get('active_accounts_list_v4'));
    }
}
