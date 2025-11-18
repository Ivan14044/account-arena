<?php
namespace App\Services;

use App\Models\AdminNotification;
use App\Models\AdminNotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /**
     * Отправить уведомление администраторам используя шаблон
     */
    public static function sendFromTemplate(string $type, string $templateCode, array $variables = [], string $status = 'danger'): void
    {
        $template = NotificationTemplate::with('translations')
            ->where('code', $templateCode)
            ->first();

        if (!$template) {
            Log::warning("Admin notification template not found: {$templateCode}");
            return;
        }

        // Получаем текущую локаль (по умолчанию ru)
        $locale = App::getLocale();
        if (!in_array($locale, ['ru', 'en', 'uk'])) {
            $locale = 'ru';
        }

        // Получаем переводы для текущей локали
        $translations = $template->translations()
            ->where('locale', $locale)
            ->pluck('value', 'code')
            ->toArray();

        // Если нет переводов для текущей локали, используем русский
        if (empty($translations['title']) || empty($translations['message'])) {
            $translations = $template->translations()
                ->where('locale', 'ru')
                ->pluck('value', 'code')
                ->toArray();
        }

        if (empty($translations['title']) || empty($translations['message'])) {
            Log::warning("Admin notification template translations not found: {$templateCode}");
            return;
        }

        // Рендерим шаблон с переменными
        $title = self::renderTemplate($translations['title'], $variables);
        $message = self::renderTemplate($translations['message'], $variables);

        // Отправляем уведомление
        self::send($type, $title, $message, $status);
    }

    /**
     * Заменить плейсхолдеры в тексте на значения переменных
     */
    protected static function renderTemplate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace(':' . $key, $value, $text);
        }

        return $text;
    }
}
