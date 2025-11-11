<?php

namespace App\Observers;

use App\Models\Option;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer для автоматической очистки кеша при изменении настроек
 */
class OptionObserver
{
    /**
     * Очистить кеш опций
     */
    private function clearOptionsCache(): void
    {
        Cache::forget('site_options');
        Cache::forget('purchase_rules');
        Log::info('Options cache cleared');
    }

    /**
     * Handle the Option "created" event.
     */
    public function created(Option $option): void
    {
        $this->clearOptionsCache();
    }

    /**
     * Handle the Option "updated" event.
     */
    public function updated(Option $option): void
    {
        $this->clearOptionsCache();
    }

    /**
     * Handle the Option "deleted" event.
     */
    public function deleted(Option $option): void
    {
        $this->clearOptionsCache();
    }
}



