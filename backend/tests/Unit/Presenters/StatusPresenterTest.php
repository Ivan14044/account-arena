<?php

namespace Tests\Unit\Presenters;

use App\Models\ProductDispute;
use App\Models\Purchase;
use App\Presenters\DisputePresenter;
use App\Presenters\PurchasePresenter;
use PHPUnit\Framework\TestCase;

/**
 * Шаг 7: презентеры статусов (presentation вынесен из моделей). Тестируем
 * детерминированные badge-классы (без __()).
 */
class StatusPresenterTest extends TestCase
{
    public function test_purchase_badge_classes(): void
    {
        $this->assertSame('warning', PurchasePresenter::statusBadgeClass(Purchase::STATUS_PENDING));
        $this->assertSame('primary', PurchasePresenter::statusBadgeClass(Purchase::STATUS_PROCESSING));
        $this->assertSame('success', PurchasePresenter::statusBadgeClass(Purchase::STATUS_COMPLETED));
        $this->assertSame('info', PurchasePresenter::statusBadgeClass(Purchase::STATUS_REFUNDED));
        $this->assertSame('secondary', PurchasePresenter::statusBadgeClass('whatever'));
    }

    public function test_dispute_badge_classes(): void
    {
        $this->assertSame('badge-warning', DisputePresenter::statusBadgeClass(ProductDispute::STATUS_NEW));
        $this->assertSame('badge-info', DisputePresenter::statusBadgeClass(ProductDispute::STATUS_IN_REVIEW));
        $this->assertSame('badge-success', DisputePresenter::statusBadgeClass(ProductDispute::STATUS_RESOLVED));
        $this->assertSame('badge-danger', DisputePresenter::statusBadgeClass(ProductDispute::STATUS_REJECTED));
        $this->assertSame('badge-secondary', DisputePresenter::statusBadgeClass('whatever'));
    }

    public function test_purchase_status_text_falls_back_to_raw(): void
    {
        // Неизвестный статус → возвращается как есть (default-ветка).
        $this->assertSame('weird_status', PurchasePresenter::statusText('weird_status'));
    }
}
