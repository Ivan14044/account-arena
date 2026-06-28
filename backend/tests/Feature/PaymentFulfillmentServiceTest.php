<?php

namespace Tests\Feature;

use App\Models\ServiceAccount;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Шаг 5: единый revalidateProducts() для платёжных вебхуков (раньше — 4 копии).
 */
class PaymentFulfillmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_revalidate_skips_out_of_stock_and_recomputes_price(): void
    {
        // Товар админа (supplier_id=null) с 10 аккаунтами в наличии, цена 100.
        $inStock = ServiceAccount::factory()->create(['price' => 100, 'used' => 0]);
        // Все аккаунты использованы → доступно 0.
        $outOfStock = ServiceAccount::factory()->create(['used' => 10]);

        $result = app(PaymentFulfillmentService::class)->revalidateProducts([
            // устаревшая цена 999 в вебхуке должна быть пересчитана на актуальную (100)
            ['product_id' => $inStock->id, 'quantity' => 1, 'price' => 999, 'total' => 999],
            // недостаточно стока → позиция отбрасывается
            ['product_id' => $outOfStock->id, 'quantity' => 5, 'price' => 10, 'total' => 50],
        ], 'TEST');

        $this->assertCount(1, $result);
        $this->assertSame($inStock->id, $result[0]['product']->id);
        $this->assertEqualsWithDelta($inStock->getCurrentPrice(), $result[0]['price'], 0.001);
        $this->assertEqualsWithDelta($inStock->getCurrentPrice() * 1, $result[0]['total'], 0.001);
    }
}
