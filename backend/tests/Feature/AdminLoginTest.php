<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Регрессионный тест для редизайна страницы входа в админ-панель:
 * страница должна быть на русском, в едином фирменном стиле (standalone,
 * без дефолтного AdminLTE-шаблона и ссылки регистрации), а ошибки входа —
 * на русском языке.
 */
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_branded_russian_view_without_register_link(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('Вход в систему');
        $response->assertSee('Account Arena');
        $response->assertSee('Войти');
        // Дефолтный AdminLTE-шаблон и саморегистрация на админке недопустимы
        $response->assertDontSee('Register a new membership');
        $response->assertDontSee('Sign in to start your session');
    }

    public function test_admin_can_log_in(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_blocked' => false,
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_gets_russian_error(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'Пользователь не найден или не является администратором.',
        ]);
        $this->assertGuest();
    }

    public function test_blocked_admin_gets_russian_error(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_blocked' => true,
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'Ваш аккаунт заблокирован.',
        ]);
        $this->assertGuest();
    }
}
