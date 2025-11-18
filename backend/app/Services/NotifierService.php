<?php
namespace App\Services;

use App\Models\AdminNotification;
use App\Models\AdminNotificationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class NotifierService
{
    /**
     * Отправить уведомление всем администраторам (с учетом их настроек)
     */
    public static function send(string $type, string $title, string $message, string $status = 'danger'): void
    {
        // Получаем всех администраторов
        $admins = User::where(function($query) {
            $query->where('is_admin', true)
                  ->orWhere('is_main_admin', true);
        })->get();

        // Проверяем, есть ли хотя бы один админ с включенным уведомлением этого типа
        $shouldCreateNotification = false;
        foreach ($admins as $admin) {
            $settings = AdminNotificationSetting::getOrCreateForUser($admin->id);
            if ($settings->isEnabled($type)) {
                $shouldCreateNotification = true;
                break;
            }
        }

        // Создаем одно уведомление (общее для всех админов)
        if ($shouldCreateNotification) {
            AdminNotification::create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'status' => $status
            ]);
        }

        // Telegram уведомление отправляем всегда (если настроено)
        if (config('telegram.bot_token') && config('telegram.chat_id')) {
            Http::post("https://api.telegram.org/bot" . config('telegram.bot_token') . "/sendMessage", [
                'chat_id' => config('telegram.chat_id'),
                'text' => "🔔 {$title}: {$message}",
            ]);
        }
    }
}
