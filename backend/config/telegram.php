<?php

return [
    // Bot API (для уведомлений)
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    
    // Client API (MadelineProto) - для поддержки через обычный аккаунт
    'client' => [
        'enabled' => env('TELEGRAM_CLIENT_ENABLED', false),
        'session_path' => storage_path('app/telegram/session.madeline'),
        'api_id' => env('TELEGRAM_API_ID'),
        'api_hash' => env('TELEGRAM_API_HASH'),
        'phone_number' => env('TELEGRAM_PHONE_NUMBER'),
    ],
];
