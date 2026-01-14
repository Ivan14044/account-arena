<?php

namespace App\Observers;

use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerObserver
{
    private function clearBannerCache(): void
    {
        Cache::forget('banners_all');
        // Clear common position caches
        Cache::forget('banners_pos_home_top');
        Cache::forget('banners_pos_home_middle');
        Cache::forget('banners_pos_home_bottom');
        Cache::forget('banners_pos_catalog_top');
    }

    public function created(Banner $banner): void
    {
        $this->clearBannerCache();
    }

    public function updated(Banner $banner): void
    {
        $this->clearBannerCache();
    }

    public function deleted(Banner $banner): void
    {
        $this->clearBannerCache();
    }

    public function restored(Banner $banner): void
    {
        $this->clearBannerCache();
    }

    public function forceDeleted(Banner $banner): void
    {
        $this->clearBannerCache();
    }
}
