<?php

namespace ArthurSalenko\KasmClient;

use Illuminate\Support\ServiceProvider;

class KasmServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/kasm.php', 'kasm');

        $this->app->singleton('kasm-client', function () {
            return new KasmClient();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/kasm.php' => config_path('kasm.php'),
        ], 'config');
    }
}
