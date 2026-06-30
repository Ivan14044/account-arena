<?php

namespace Tests\Feature;

use App\Models\ServiceAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регрессия: API exception handler должен рендерить framework-исключения
 * корректными статусами, а не маскировать всё под 500.
 *
 * До фикса `App\Exceptions\Handler::render` для api/* отдавал 500 на любой
 * брошенный в контроллере ValidationException (без ключа `errors`) и на
 * AuthenticationException.
 */
class ExceptionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_validation_exception_renders_as_422_with_errors(): void
    {
        $user = User::factory()->create();
        $supplier = User::factory()->create(['is_supplier' => true]);
        $account = ServiceAccount::factory()->create(['supplier_id' => $supplier->id]);

        // Спор по возвращённой транзакции → контроллер бросает ValidationException.
        // До фикса это маскировалось под 500; должно быть 422 с message + errors.
        $tx = Transaction::factory()->create([
            'user_id' => $user->id,
            'service_account_id' => $account->id,
            'status' => 'refunded',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/disputes', [
            'transaction_id' => $tx->id,
            'reason' => 'not_working',
            'description' => 'Account does not work',
            'screenshot_link' => 'https://example.com/proof.png',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_request_renders_as_401(): void
    {
        // Гость на защищённом auth:sanctum роуте → 401, а не 500.
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
