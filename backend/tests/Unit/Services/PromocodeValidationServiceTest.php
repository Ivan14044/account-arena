<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PromocodeValidationService;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
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
            'percent_discount' => 20,
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
            'percent_discount' => 10,
            'usage_limit' => 100,
            'usage_count' => 0,
            'expires_at' => Carbon::now()->subDays(1),
        ]);

        $result = $this->service->validate('EXPIRED', null);

        $this->assertFalse($result['ok']);
        // Проверяем статус (locale-независимо), а не текст сообщения
        $this->assertEquals('expired', $result['status']);
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
            'percent_discount' => 15,
            'usage_limit' => 10,
            'usage_count' => 10,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $result = $this->service->validate('LIMITED', null);

        $this->assertFalse($result['ok']);
        // Проверяем статус (locale-независимо), а не текст сообщения
        $this->assertEquals('exhausted', $result['status']);
    }

    /** @test */
    public function it_is_case_insensitive()
    {
        Promocode::create([
            'code' => 'SAVE10',
            'type' => 'discount',
            'percent_discount' => 10,
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

    /** @test */
    public function it_enforces_guest_usage_limit_of_five()
    {
        $promo = Promocode::create([
            'code' => 'GUEST5',
            'type' => 'discount',
            'percent_discount' => 10,
            'usage_limit' => 0, // безлимитный глобальный лимит — проверяем только гостевой
            'usage_count' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);
        $email = 'guest@example.com';

        // 4 использования этим гостем → промокод всё ещё доступен
        for ($i = 0; $i < 4; $i++) {
            PromocodeUsage::create([
                'promocode_id' => $promo->id,
                'guest_email' => $email,
                'order_id' => "guest-order-{$i}",
            ]);
        }
        $this->assertTrue($this->service->validate('GUEST5', null, $email)['ok']);

        // 5-е использование → достигнут лимит (5) → отказ
        PromocodeUsage::create([
            'promocode_id' => $promo->id,
            'guest_email' => $email,
            'order_id' => 'guest-order-4',
        ]);
        $result = $this->service->validate('GUEST5', null, $email);
        $this->assertFalse($result['ok']);
        $this->assertEquals('guest_limit', $result['status']);
    }

    /** @test */
    public function guest_limit_is_per_email_and_case_insensitive()
    {
        $promo = Promocode::create([
            'code' => 'GUESTCI',
            'type' => 'discount',
            'percent_discount' => 10,
            'usage_limit' => 0,
            'usage_count' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Исчерпываем лимит для guest1 (хранится в нижнем регистре)
        for ($i = 0; $i < 5; $i++) {
            PromocodeUsage::create([
                'promocode_id' => $promo->id,
                'guest_email' => 'guest1@example.com',
                'order_id' => "ci-order-{$i}",
            ]);
        }

        // Тот же email в другом регистре → тоже отказ (нормализация)
        $this->assertFalse($this->service->validate('GUESTCI', null, 'Guest1@Example.com')['ok']);

        // Другой гость → промокод доступен
        $this->assertTrue($this->service->validate('GUESTCI', null, 'guest2@example.com')['ok']);
    }
}



