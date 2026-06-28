<?php

namespace Tests\Feature\Api;

use App\Models\ProductDispute;
use App\Models\ServiceAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регрессионные тесты для SECURITY FIX (H15): валидация создания спора.
 */
class DisputeStoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeDisputableTransaction(User $user, string $status = 'completed'): Transaction
    {
        // Товар привязываем к поставщику: на MySQL product_disputes.supplier_id
        // nullable, но в тестовой sqlite миграция-nullable пропускается, поэтому
        // используем supplier-товар, чтобы supplier_id был не-null.
        $supplier = User::factory()->create(['is_supplier' => true]);
        $account = ServiceAccount::factory()->create(['supplier_id' => $supplier->id]);

        return Transaction::factory()->create([
            'user_id' => $user->id,
            'service_account_id' => $account->id,
            'status' => $status,
            'created_at' => now()->subDay(),
        ]);
    }

    public function test_cannot_open_dispute_on_refunded_transaction(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeDisputableTransaction($user, 'refunded');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/disputes', [
            'transaction_id' => $tx->id,
            'reason' => 'not_working',
            'description' => 'Account does not work',
            'screenshot_link' => 'https://example.com/proof.png',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('product_disputes', 0);
    }

    public function test_cannot_open_duplicate_dispute(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeDisputableTransaction($user, 'completed');

        $payload = [
            'transaction_id' => $tx->id,
            'reason' => 'not_working',
            'description' => 'Account does not work',
            'screenshot_link' => 'https://example.com/proof.png',
        ];

        $first = $this->actingAs($user, 'sanctum')->postJson('/api/disputes', $payload);
        $first->assertStatus(201);

        $second = $this->actingAs($user, 'sanctum')->postJson('/api/disputes', $payload);
        $second->assertStatus(422);

        $this->assertSame(1, ProductDispute::where('transaction_id', $tx->id)->count());
    }
}
