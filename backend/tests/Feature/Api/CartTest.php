<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\ServiceAccount;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_purchase_product_with_balance()
    {
        $user = User::factory()->create(['balance' => 100.00]);
        $product = ServiceAccount::factory()->withAccounts(10)->create(['price' => 50.00]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'products' => [
                    ['id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => 'balance',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Проверяем, что создана покупка
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'service_account_id' => $product->id,
            'quantity' => 1,
            'status' => 'completed',
        ]);

        // Проверяем списание баланса
        $user->refresh();
        $this->assertEquals(50.00, $user->balance);
    }

    /** @test */
    public function user_cannot_purchase_with_insufficient_balance()
    {
        $user = User::factory()->create(['balance' => 10.00]);
        $product = ServiceAccount::factory()->withAccounts(10)->create(['price' => 50.00]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'products' => [
                    ['id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => 'balance',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', function($message) {
                return str_contains($message, 'Insufficient balance');
            });

        $this->assertEquals(0, Purchase::count());
    }

    /** @test */
    public function user_cannot_purchase_out_of_stock_product()
    {
        $user = User::factory()->create(['balance' => 100.00]);
        $product = ServiceAccount::factory()->withAccounts(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'products' => [
                    ['id' => $product->id, 'quantity' => 5],
                ],
                'payment_method' => 'balance',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', function($message) {
                return str_contains($message, 'Insufficient stock');
            });
    }

    /** @test */
    public function purchase_assigns_correct_accounts_to_user()
    {
        $user = User::factory()->create(['balance' => 100.00]);
        
        $accounts = ['acc1:pass1', 'acc2:pass2', 'acc3:pass3'];
        $product = ServiceAccount::factory()->create([
            'price' => 10.00,
            'accounts_data' => $accounts,
            'used' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'products' => [
                    ['id' => $product->id, 'quantity' => 2],
                ],
                'payment_method' => 'balance',
            ]);

        $response->assertOk();

        $purchase = Purchase::where('user_id', $user->id)->first();
        
        $this->assertEquals(['acc1:pass1', 'acc2:pass2'], $purchase->account_data);
        
        // Проверяем обновление счетчика
        $product->refresh();
        $this->assertEquals(2, $product->used);
    }

    /** @test */
    public function purchase_creates_order_number()
    {
        $user = User::factory()->create(['balance' => 100.00]);
        $product = ServiceAccount::factory()->withAccounts(5)->create(['price' => 20.00]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'products' => [
                    ['id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => 'balance',
            ]);

        $purchase = Purchase::where('user_id', $user->id)->first();
        
        $this->assertNotNull($purchase->order_number);
        $this->assertStringStartsWith('ORD-', $purchase->order_number);
    }

    /** @test */
    public function user_can_purchase_multiple_products_at_once()
    {
        $user = User::factory()->create(['balance' => 500.00]);
        
        $product1 = ServiceAccount::factory()->withAccounts(10)->create(['price' => 50.00]);
        $product2 = ServiceAccount::factory()->withAccounts(5)->create(['price' => 100.00]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'products' => [
                    ['id' => $product1->id, 'quantity' => 2],
                    ['id' => $product2->id, 'quantity' => 1],
                ],
                'payment_method' => 'balance',
            ]);

        $response->assertOk();

        $this->assertEquals(2, Purchase::where('user_id', $user->id)->count());
        
        // Проверяем баланс: 500 - (50*2 + 100*1) = 300
        $user->refresh();
        $this->assertEquals(300.00, $user->balance);
    }

    /** @test */
    public function guest_cannot_use_balance_payment_method()
    {
        $product = ServiceAccount::factory()->withAccounts(10)->create();

        $response = $this->postJson('/api/cart', [
            'products' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => 'balance',
        ]);

        $response->assertUnauthorized();
    }
}



