<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PromocodeValidationService;
use App\Models\Promocode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PromocodeValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromocodeValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromocodeValidationService();
    }

    /** @test */
    public function it_validates_discount_promocode()
    {
        $promo = Promocode::create([
            'code' => 'DISCOUNT20',
            'type' => 'discount',
            'discount_percent' => 20,
            'usage_limit' => 100,
            'usage_count' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $result = $this->service->validate('DISCOUNT20', null);

        $this->assertTrue($result['ok']);
        $this->assertEquals('discount', $result['type']);
        $this->assertEquals(20, $result['discount_percent']);
    }

    /** @test */
    public function it_rejects_expired_promocode()
    {
        Promocode::create([
            'code' => 'EXPIRED',
            'type' => 'discount',
            'discount_percent' => 10,
            'usage_limit' => 100,
            'usage_count' => 0,
            'expires_at' => Carbon::now()->subDays(1),
        ]);

        $result = $this->service->validate('EXPIRED', null);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('истек', $result['message']);
    }

    /** @test */
    public function it_rejects_non_existent_promocode()
    {
        $result = $this->service->validate('NONEXISTENT', null);

        $this->assertFalse($result['ok']);
    }

    /** @test */
    public function it_rejects_promocode_at_usage_limit()
    {
        Promocode::create([
            'code' => 'LIMITED',
            'type' => 'discount',
            'discount_percent' => 15,
            'usage_limit' => 10,
            'usage_count' => 10,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $result = $this->service->validate('LIMITED', null);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('использован', $result['message']);
    }

    /** @test */
    public function it_is_case_insensitive()
    {
        Promocode::create([
            'code' => 'SAVE10',
            'type' => 'discount',
            'discount_percent' => 10,
            'usage_limit' => 100,
            'usage_count' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $result1 = $this->service->validate('save10', null);
        $result2 = $this->service->validate('SAVE10', null);
        $result3 = $this->service->validate('SaVe10', null);

        $this->assertTrue($result1['ok']);
        $this->assertTrue($result2['ok']);
        $this->assertTrue($result3['ok']);
    }
}



