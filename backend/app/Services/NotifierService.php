<?php
namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Http;

class NotifierService
{
    public static function send(string $type, string $title, string $message, string $status = 'danger'): void
    {
        AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'status' => $status
        ]);

        if (config('telegram.bot_token') && config('telegram.chat_id')) {
            Http::post("https://api.telegram.org/bot" . config('telegram.bot_token') . "/sendMessage", [
                'chat_id' => config('telegram.chat_id'),
                'text' => "🔔 {$title}: {$message}",
            ]);
        }
    }
}
