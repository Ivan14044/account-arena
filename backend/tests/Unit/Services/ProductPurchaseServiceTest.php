<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductPurchaseService;
use App\Models\ServiceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductPurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductPurchaseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductPurchaseService();
    }

    /** @test */
    public function it_prepares_products_data_successfully()
    {
        $product = ServiceAccount::factory()->withAccounts(10)->create();

        $request = [
            ['id' => $product->id, 'quantity' => 2],
        ];

        $result = $this->service->prepareProductsData($request);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($product->id, $result['data'][0]['product']->id);
        $this->assertEquals(2, $result['data'][0]['quantity']);
    }

    /** @test */
    public function it_fails_when_product_not_found()
    {
        $request = [
            ['id' => 99999, 'quantity' => 1],
        ];

        $result = $this->service->prepareProductsData($request);

        $this->assertFalse($result['success']);
        $this->assertEquals('Product not found', $result['message']);
    }

    /** @test */
    public function it_fails_when_insufficient_stock()
    {
        $product = ServiceAccount::factory()->withAccounts(5)->create();

        $request = [
            ['id' => $product->id, 'quantity' => 10],
        ];

        $result = $this->service->prepareProductsData($request);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient stock', $result['message']);
    }

    /** @test */
    public function it_creates_product_purchase_successfully()
    {
        $product = ServiceAccount::factory()->withAccounts(10)->create();
        $user = User::factory()->create();

        $result = $this->service->createProductPurchase(
            product: $product,
            quantity: 2,
            price: 10.00,
            total: 20.00,
            userId: $user->id
        );

        $this->assertArrayHasKey('transaction', $result);
        $this->assertArrayHasKey('purchase', $result);
        
        $this->assertEquals(2, $result['purchase']->quantity);
        $this->assertEquals(20.00, $result['purchase']->total_amount);
        $this->assertCount(2, $result['purchase']->account_data);

        // Проверяем, что счетчик использованных увеличился
        $product->refresh();
        $this->assertEquals(2, $product->used);
    }

    /** @test */
    public function it_assigns_correct_accounts_from_pool()
    {
        $accounts = ['acc1:pass1', 'acc2:pass2', 'acc3:pass3'];
        $product = ServiceAccount::factory()->create([
            'accounts_data' => $accounts,
            'used' => 0,
        ]);

        $result = $this->service->createProductPurchase(
            product: $product,
            quantity: 2,
            price: 10.00,
            total: 20.00,
            userId: 1
        );

        $assignedAccounts = $result['purchase']->account_data;
        
        $this->assertEquals(['acc1:pass1', 'acc2:pass2'], $assignedAccounts);
    }

    /** @test */
    public function it_handles_guest_purchases()
    {
        $product = ServiceAccount::factory()->withAccounts(5)->create();
        $guestEmail = 'guest@example.com';

        $result = $this->service->createProductPurchase(
            product: $product,
            quantity: 1,
            price: 15.00,
            total: 15.00,
            userId: null,
            guestEmail: $guestEmail,
            paymentMethod: 'guest_purchase'
        );

        $this->assertNull($result['purchase']->user_id);
        $this->assertEquals($guestEmail, $result['purchase']->guest_email);
        $this->assertEquals('guest_purchase', $result['transaction']->payment_method);
    }

    /** @test */
    public function it_creates_multiple_purchases_in_transaction()
    {
        $product1 = ServiceAccount::factory()->withAccounts(10)->create();
        $product2 = ServiceAccount::factory()->withAccounts(5)->create();
        $user = User::factory()->create();

        $productsData = [
            [
                'product' => $product1,
                'quantity' => 2,
                'price' => 10.00,
                'total' => 20.00,
            ],
            [
                'product' => $product2,
                'quantity' => 1,
                'price' => 15.00,
                'total' => 15.00,
            ],
        ];

        $purchases = $this->service->createMultiplePurchases(
            productsData: $productsData,
            userId: $user->id
        );

        $this->assertCount(2, $purchases);
        $this->assertEquals(2, $purchases[0]->quantity);
        $this->assertEquals(1, $purchases[1]->quantity);

        // Проверяем обновление счетчиков
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(2, $product1->used);
        $this->assertEquals(1, $product2->used);
    }
}



