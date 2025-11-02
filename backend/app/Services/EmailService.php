<?php

namespace App\Services;

use App\Models\User;
use App\Models\Option;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Collection;
use Throwable;

class EmailService
{
    public static function send(string $templateCode, int $userId, array $params = []): bool
    {
        try {
            $user = User::findOrFail($userId);
            $locale = $user->lang ?? 'en';

            App::setLocale($locale);

            $translation = self::getTemplateTranslation($templateCode, $locale);

            if (!$translation || !isset($translation['title'], $translation['message'])) {
                throw new \Exception("Email template translation missing title or message.");
            }

            self::configureMailFromOptions();

            $subject = self::renderTemplate($translation['title'], $params);
            $body = self::renderTemplate($translation['message'], $params);

            Mail::send('emails.base', [
                'subject' => $subject,
                'body' => $body,
            ], function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
            });

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    public static function getTemplateTranslation(string $code, string $locale): ?array
    {
        $template = EmailTemplate::where('code', $code)
            ->with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->first();

        if (!$template || $template->translations->isEmpty()) {
            $template = EmailTemplate::where('code', $code)
                ->with(['translations' => fn($q) => $q->where('locale', 'en')])
                ->first();
        }

        if (!$template || $template->translations->isEmpty()) {
            return null;
        }

        return $template->translations
            ->pluck('value', 'code')
            ->toArray();
    }

    public static function configureMailFromOptions(): void
    {
        Config::set('mail.mailers.dynamic', [
            'transport' => 'smtp',
            'host' => Option::get('host'),
            'encryption' => Option::get('encryption'),
            'port' => Option::get('port'),
            'username' => Option::get('username'),
            'password' => Option::get('password'),
            'timeout' => null,
            'auth_mode' => null,
        ]);

        Config::set('mail.default', 'dynamic');

        Config::set('mail.from', [
            'address' => Option::get('from_address'),
            'name' => Option::get('from_name'),
        ]);
    }

    public static function renderTemplate(?string $text, array $params): ?string
    {
        if (!$text) {
            return null;
        }

        foreach ($params as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }

        return $text;
    }
}
