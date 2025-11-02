<?php

namespace App\Notifications;

use App\Services\EmailService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;

class ResetPasswordNotification extends Notification
{
    public $token;
    public $user;

    public function __construct($token, $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $locale = $this->user->lang ?? 'en';
        \App::setLocale($locale);

        $translation = EmailService::getTemplateTranslation('reset_password', $locale);
        $url = url('/reset-password/' . $this->token . '?email=' . urlencode($this->user->email));

        $subject = $translation['title'] ?? null;
        $body = view('emails.reset-password', [
            'translation' => $translation,
            'url' => $url,
            'email' => $this->user->email,
        ])->render();

        return (new \Illuminate\Notifications\Messages\MailMessage())
            ->subject($subject)
            ->view('emails.base', [
                'subject' => $subject,
                'body' => $body,
                'button' => false
            ]);
    }
}
