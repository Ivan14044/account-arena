<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Единый источник истины для кэш-ключей каталога товаров.
 *
 * Раньше ключ списка каталога и его инвалидация были раскопированы по 4 файлам
 * (Api\AccountController читал `active_accounts_list_v4`, а три инвалидатора —
 * ServiceAccountObserver/CategoryObserver/Admin\ServiceAccountController — чистили
 * список ключей вручную). Любое добавление версии ключа требовало правок в
 * нескольких местах; пропуск приводил к стейл-стоку (исторический баг M1).
 *
 * Этот класс централизует имена ключей, TTL и инвалидацию. Поведение прежнее:
 * те же ключи, те же forget'ы.
 */
class ProductCache
{
    /** Актуальный ключ кэша списка активного каталога (читается в Api\AccountController). */
    public const CATALOG_LIST = 'active_accounts_list_v4';

    /** TTL списка каталога, секунды. */
    public const CATALOG_LIST_TTL = 300;

    /** TTL кэша «похожих товаров», секунды. */
    public const SIMILAR_TTL = 3600;

    /**
     * Устаревшие версии ключа списка. Больше не пишутся, но всё ещё сбрасываются
     * при инвалидации — на случай, если в каком-то кэше остались старые значения
     * во время выкатки.
     */
    private const LEGACY_CATALOG_LISTS = [
        'active_accounts_list',
        'active_accounts_list_v2',
        'active_accounts_list_v3',
    ];

    /** Ключ кэша «похожих товаров» для конкретного товара и лимита. */
    public static function similarKey(int $productId, int $limit): string
    {
        return "similar_products_v2_{$productId}_{$limit}";
    }

    /** Сбросить кэш списка каталога (актуальный + устаревшие ключи). */
    public static function flushCatalog(): void
    {
        Cache::forget(self::CATALOG_LIST);

        foreach (self::LEGACY_CATALOG_LISTS as $key) {
            Cache::forget($key);
        }
    }
}
