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
    /**
     * Отправка email зарегистрированному пользователю
     */
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

            Mail::to($user->email)->queue(new \App\Mail\BaseMail($subject, $body));

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Отправка email гостю (пользователю без регистрации)
     */
    public static function sendToGuest(string $email, string $templateCode, array $params = []): bool
    {
        try {
            // Используем язык по умолчанию для гостей
            $locale = Option::get('default_lang', 'en');

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
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
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
        $host = Option::get('smtp_host');
        $port = Option::get('smtp_port');
        $encryption = Option::get('smtp_encryption');
        $username = Option::get('smtp_username');
        $password = Option::get('smtp_password');

        // Validate required settings
        if (empty($host) || empty($port) || empty($username) || empty($password)) {
            throw new \Exception('SMTP settings are incomplete. Please configure host, port, username, and password in admin settings.');
        }

        // Normalize encryption: empty string or null should be null (no encryption)
        $encryption = in_array(strtolower(trim($encryption ?? '')), ['tls', 'ssl']) ? strtolower(trim($encryption)) : null;

        // Normalize port to integer
        $port = (int) $port;

        // Log configuration for debugging (without password)
        \Log::debug('SMTP Configuration', [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption ?? 'null (no encryption)',
            'username' => $username,
        ]);

        Config::set('mail.mailers.dynamic', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption, // null means no encryption
            'username' => $username,
            'password' => $password,
            'timeout' => 30,
            'verify_peer' => false, // Some SMTP servers have SSL issues
        ]);

        Config::set('mail.default', 'dynamic');

        $fromAddress = Option::get('smtp_from_address');
        $fromName = Option::get('smtp_from_name');

        Config::set('mail.from', [
            'address' => $fromAddress ?: 'noreply@example.com',
            'name' => $fromName ?: 'Account Arena',
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
