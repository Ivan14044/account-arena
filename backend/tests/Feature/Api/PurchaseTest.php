<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Purchase;
use App\Models\ServiceAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_list_own_purchases()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPurchases = Purchase::factory()->count(3)->forUser($user)->create();
        $otherPurchases = Purchase::factory()->count(2)->forUser($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/purchases');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'purchases');
    }

    /** @test */
    public function user_cannot_see_other_user_purchases()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Purchase::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/purchases');

        $response->assertOk()
            ->assertJsonCount(0, 'purchases');
    }

    /** @test */
    public function guest_cannot_access_purchases_list()
    {
        $response = $this->getJson('/api/purchases');

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_can_view_own_purchase_details()
    {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/purchases/{$purchase->id}");

        $response->assertOk()
            ->assertJsonPath('purchase.id', $purchase->id)
            ->assertJsonPath('purchase.order_number', $purchase->order_number);
    }

    /** @test */
    public function user_cannot_view_other_user_purchase()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $purchase = Purchase::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/purchases/{$purchase->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function user_can_download_own_purchase()
    {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->forUser($user)->create([
            'account_data' => ['account1:pass1', 'account2:pass2'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/purchases/{$purchase->id}/download");

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertHeader('Content-Disposition');

        $content = $response->getContent();
        $this->assertStringContainsString('account1:pass1', $content);
        $this->assertStringContainsString('account2:pass2', $content);
    }

    /** @test */
    public function user_cannot_download_other_user_purchase()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $purchase = Purchase::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/purchases/{$purchase->id}/download");

        $response->assertNotFound();
    }

    /** @test */
    public function download_filename_contains_order_number()
    {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->forUser($user)->create([
            'order_number' => 'ORD-20251106-12345',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/purchases/{$purchase->id}/download");

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('ORD-20251106-12345', $disposition);
    }

    /** @test */
    public function purchases_can_be_filtered_by_date()
    {
        $user = User::factory()->create();
        
        $oldPurchase = Purchase::factory()->forUser($user)->create([
            'created_at' => now()->subDays(10),
        ]);
        
        $recentPurchase = Purchase::factory()->forUser($user)->create([
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/purchases?date_from=' . now()->subDays(5)->toDateString());

        $response->assertOk();
        
        $purchaseIds = collect($response->json('purchases'))->pluck('id')->toArray();
        
        $this->assertNotContains($oldPurchase->id, $purchaseIds);
        $this->assertContains($recentPurchase->id, $purchaseIds);
    }
}



