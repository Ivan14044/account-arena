<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class NotificationTemplateService
{
    /**
     * Send a notification to a user based on a template code and variables.
     *
     * @param \App\Models\User $user
     * @param string $templateCode
     * @param array $variables
     * @return Notification|null
     */
    public function sendToUser($user, string $templateCode, array $variables = []): ?Notification
    {
        $template = NotificationTemplate::with('translations')
            ->where('code', $templateCode)
            ->first();

        if (!$template) {
            Log::warning("Notification template not found: {$templateCode}");
            return null;
        }

        return Notification::create([
            'user_id' => $user->id,
            'notification_template_id' => $template->id,
            'variables' => $variables,
        ]);
    }

    /**
     * Replace placeholders in text with given variables.
     *
     * @param string $text
     * @param array $variables
     * @return string
     */
    protected function render(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace(':' . $key, $value, $text);
        }

        return $text;
    }
}
