<?php

namespace Tests\Unit\Services;

use App\Services\PricingService;
use PHPUnit\Framework\TestCase;

/**
 * Шаг 4 рефакторинга: единый расчёт суммы заказа. Тесты документируют
 * поведение (как у авторизованных) и фиксируют исправление гостевого бага
 * (кэп 99% + корректное округление вместо (int)-усечения процента).
 */
class PricingServiceTest extends TestCase
{
    public function test_no_discount_returns_base(): void
    {
        $this->assertEqualsWithDelta(100.00, PricingService::computeOrderTotal(100.0, null, null), 0.001);
    }

    public function test_promo_discount_applies(): void
    {
        $this->assertEqualsWithDelta(
            90.00,
            PricingService::computeOrderTotal(100.0, null, ['type' => 'discount', 'discount_percent' => 10]),
            0.001
        );
    }

    public function test_discount_capped_at_99_percent_for_guests(): void
    {
        // 150% → кэп 99% → итог = 1% базы (раньше у гостей кэпа не было).
        $this->assertEqualsWithDelta(
            1.00,
            PricingService::computeOrderTotal(100.0, null, ['type' => 'discount', 'discount_percent' => 150]),
            0.001
        );
    }

    public function test_fractional_percent_not_int_truncated(): void
    {
        // 33.33% → 66.67 (раньше у гостей (int) усекал до 33% → 67.00).
        $this->assertEqualsWithDelta(
            66.67,
            PricingService::computeOrderTotal(100.0, null, ['type' => 'discount', 'discount_percent' => 33.33]),
            0.001
        );
    }

    public function test_min_charge_floor(): void
    {
        $this->assertEqualsWithDelta(0.01, PricingService::computeOrderTotal(0.0, null, null), 0.0001);
    }

    public function test_non_discount_promo_is_ignored(): void
    {
        $this->assertEqualsWithDelta(
            100.00,
            PricingService::computeOrderTotal(100.0, null, ['type' => 'voucher', 'discount_percent' => 50]),
            0.001
        );
    }
}
