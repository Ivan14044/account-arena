<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

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
        // Inject dynamic badge for Browser Sessions into AdminLTE menu, with backend-cached count
        $baseUrl = config('services.browser_api.url', env('BROWSER_API_URL', 'http://workspace.subcloudy.com/api/'));

        $activeCount = Cache::remember('browser_sessions.active_count', 60, function () use ($baseUrl) {
            try {
                $response = Http::timeout(3)->get(rtrim($baseUrl, '/') . '/list');
                if ($response->ok()) {
                    $sessions = $response->json('sessions') ?? [];
                    $count = 0;
                    foreach ($sessions as $s) {
                        if (!empty($s['active'])) { $count++; }
                    }
                    return $count;
                }
            } catch (\Throwable $e) {
                // ignore and fallthrough to default 0
            }
            return 0;
        });

        $menu = Config::get('adminlte.menu', []);
        foreach ($menu as $idx => $item) {
            if (is_array($item) && ($item['url'] ?? null) === 'browser-sessions') {
                $item['label'] = $activeCount;
                $item['label_color'] = $activeCount > 0 ? 'info' : 'secondary';
                $menu[$idx] = $item;
                break;
            }
        }

        Config::set('adminlte.menu', $menu);
    }
}
