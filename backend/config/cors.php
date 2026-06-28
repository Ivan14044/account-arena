<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],

    /*
    | SECURITY FIX (C8 / bug C8): нельзя сочетать allowed_origins=['*'] с
    | supports_credentials=true. Origins берутся из ENV (CORS_ALLOWED_ORIGINS,
    | список через запятую) с безопасным дефолтом — собственный APP_URL.
    | Витрина (SPA) отдаётся самим Laravel, т.е. same-origin; для отдельного
    | фронтенд-домена/деплоя добавьте его в CORS_ALLOWED_ORIGINS.
    */
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', (string) env('APP_URL')))
    ))),

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
