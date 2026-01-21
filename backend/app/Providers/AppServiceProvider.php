<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\SupportMessage;
use App\Models\SupportChat;
use App\Models\ProductDispute;
use App\Models\Purchase;
use App\Models\Category;
use App\Models\ServiceAccount;
use App\Models\Banner;
use App\Models\Option;
use App\Observers\CategoryObserver;
use App\Observers\ServiceAccountObserver;
use App\Observers\BannerObserver;
use App\Observers\OptionObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Регистрация Observer-ов
        Category::observe(CategoryObserver::class);
        ServiceAccount::observe(ServiceAccountObserver::class);
        Banner::observe(BannerObserver::class);
        Option::observe(OptionObserver::class);

        // Глобальный скрипт для обновления badge "Ручная обработка" на всех страницах админ-панели
        \View::composer('adminlte::page', function ($view) {
            $view->with('manualDeliveryBadgeScript', true);
        });
    }
}
