<?php

namespace Tests\Feature\Auth;

use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регрессия (user enumeration): forgot-password не должен раскрывать,
 * зарегистрирован ли email. До фикса `exists:users,email` давал 422 на
 * незнакомый email — атакующий отличал зарегистрированные адреса.
 */
class PasswordResetEnumerationTest extends TestCase
{
    use RefreshDatabase;

    private function configureSmtpOptions(): void
    {
        // forgot-password дергает EmailService::configureMailFromOptions(),
        // который бросает 500 без настроек SMTP. Для незнакомого email письмо
        // НЕ отправляется (broker → INVALID_USER), поэтому реальный коннект не нужен.
        Option::set('smtp_host', 'smtp.example.com');
        Option::set('smtp_port', '587');
        Option::set('smtp_username', 'mailer@example.com');
        Option::set('smtp_password', 'secret');
    }

    public function test_forgot_password_with_unknown_email_returns_generic_success(): void
    {
        $this->configureSmtpOptions();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'definitely-not-registered@example.com',
        ]);

        // Generic-успех (200), а не 422 — существование email не раскрывается.
        $response->assertStatus(200);
    }

    public function test_forgot_password_still_validates_email_format(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }
}
