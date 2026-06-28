<?php

namespace Tests\Feature\Supplier;

use App\Models\ServiceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регрессионный тест для SECURITY FIX (H5): любое редактирование товара
 * поставщиком должно возвращать товар на повторную модерацию, иначе
 * одобренный товар можно незаметно подменить и протащить в прод.
 */
class ProductModerationResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_edit_resets_moderation_to_pending_and_hides_product(): void
    {
        $supplier = User::factory()->create([
            'is_supplier' => true,
            'is_blocked' => false,
        ]);

        $product = ServiceAccount::factory()->create([
            'supplier_id' => $supplier->id,
            'moderation_status' => 'approved',
            'is_active' => true,
        ]);

        $response = $this->actingAs($supplier)->put("/supplier/products/{$product->id}", [
            'title' => 'Подменённый заголовок',
            'price' => 12.34,
            'is_active' => true, // поставщик пытается оставить товар активным
        ]);

        $response->assertRedirect(route('supplier.products.index'));

        $product->refresh();

        // Главное: правка вернула товар на модерацию и скрыла его до одобрения
        $this->assertSame('pending', $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertSame('Подменённый заголовок', $product->title);
    }
}
