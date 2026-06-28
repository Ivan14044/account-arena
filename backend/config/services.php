<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'wayforpay' => [
        'merchant_account' => env('WAYFORPAY_MERCHANT_ACCOUNT'),
        'secret_key' => env('WAYFORPAY_SECRET_KEY'),
        'domain_name' => env('WAYFORPAY_DOMAIN_NAME'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    'browser_api' => [
        'url' => env('BROWSER_API_URL', 'https://workspace.account-arena.com/api/'),
    ],

    'telegram' => [
        // SECURITY FIX (H4): секрет для проверки входящих вебхуков Telegram.
        // Если задан — handle() требует совпадения заголовка
        // X-Telegram-Bot-Api-Secret-Token, а setWebhook регистрирует secret_token.
        // Если пуст — поведение остаётся прежним (обратная совместимость).
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

];
