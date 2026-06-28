<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * FIX (C9 / bug C9): ранее EmailService::send() ссылался на несуществующий
 * App\Mail\BaseMail, из-за чего ВСЕ письма зарегистрированным пользователям
 * молча падали (исключение глоталось try/catch в сервисе). Этот класс
 * восстанавливает рассылку, рендерит общий шаблон emails.base.
 */
class BaseMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Тема письма (subjectLine, чтобы не конфликтовать со свойством Mailable::$subject). */
    public string $subjectLine;

    /** Готовый HTML-текст письма (уже отрендеренный из шаблона). */
    public string $body;

    public function __construct(string $subject, string $body)
    {
        $this->subjectLine = $subject;
        $this->body = $body;
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.base')
            ->with([
                'subject' => $this->subjectLine,
                'body' => $this->body,
            ]);
    }
}
