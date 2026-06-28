<?php

namespace Tests\Feature;

use App\Models\ServiceAccount;
use App\Models\SupplierEarning;
use App\Models\User;
use App\Services\ProductPurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрытие извлечённого метода ProductPurchaseService::createSupplierEarning()
 * (шаг 6): покупка товара поставщика создаёт held-earning с суммой = total*(1-commission).
 */
class SupplierEarningCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_product_purchase_creates_held_earning(): void
    {
        $supplier = User::factory()->create([
            'is_supplier' => true,
            'supplier_commission' => 10,
            'supplier_hold_hours' => 6,
        ]);
        $product = ServiceAccount::factory()->create([
            'supplier_id' => $supplier->id,
            'price' => 100,
            'used' => 0,
        ]);
        $buyer = User::factory()->create();

        $price = $product->getCurrentPrice(); // = 100 / (1 - 0.10) = 111.11
        $result = app(ProductPurchaseService::class)
            ->createProductPurchase($product, 1, $price, $price, $buyer->id, null, 'balance');

        $earning = SupplierEarning::where('transaction_id', $result['transaction']->id)
            ->where('supplier_id', $supplier->id)
            ->first();

        $this->assertNotNull($earning, 'SupplierEarning должен быть создан');
        $this->assertSame(SupplierEarning::STATUS_HELD, $earning->status);
        // amount = total * (1 - commission/100) = price * 0.9 (= базовая цена поставщика 100)
        $this->assertEqualsWithDelta(round($price * 0.9, 2), (float) $earning->amount, 0.01);
    }

    public function test_admin_product_purchase_creates_no_earning(): void
    {
        $product = ServiceAccount::factory()->create(['supplier_id' => null, 'price' => 50, 'used' => 0]);
        $buyer = User::factory()->create();

        $result = app(ProductPurchaseService::class)
            ->createProductPurchase($product, 1, 50.0, 50.0, $buyer->id, null, 'balance');

        $this->assertSame(0, SupplierEarning::where('transaction_id', $result['transaction']->id)->count());
    }
}
