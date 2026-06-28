<?php

namespace App\Observers;

use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer для автоматической очистки кеша при изменении товаров
 */
class ServiceAccountObserver
{
    /**
     * Очистить кеш списка товаров
     */
    private function clearAccountsCache(): void
    {
        Cache::forget('active_accounts_list');
        Cache::forget('active_accounts_list_v2');
        Cache::forget('active_accounts_list_v3');
        // FIX (M1 / bug M1): актуальный ключ чтения каталога — _v4. Раньше
        // инвалидаторы чистили только устаревшие _v1.._v3, а реально читаемый
        // active_accounts_list_v4 НИКОГДА не сбрасывался → стейл-сток (оверселл),
        // стейл-цена и задержка скрытия снятых/отклонённых товаров до 5 минут.
        Cache::forget('active_accounts_list_v4');
        Log::info('ServiceAccount cache cleared');
    }

    /**
     * Handle the ServiceAccount "created" event.
     */
    public function created(ServiceAccount $serviceAccount): void
    {
        $this->clearAccountsCache();
    }

    /**
     * Handle the ServiceAccount "updated" event.
     */
    public function updated(ServiceAccount $serviceAccount): void
    {
        $this->clearAccountsCache();
    }

    /**
     * Handle the ServiceAccount "deleted" event.
     */
    public function deleted(ServiceAccount $serviceAccount): void
    {
        $this->clearAccountsCache();
    }

    /**
     * Handle the ServiceAccount "restored" event.
     */
    public function restored(ServiceAccount $serviceAccount): void
    {
        $this->clearAccountsCache();
    }

    /**
     * Handle the ServiceAccount "force deleted" event.
     */
    public function forceDeleted(ServiceAccount $serviceAccount): void
    {
        $this->clearAccountsCache();
    }
}



