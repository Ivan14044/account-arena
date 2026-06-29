<?php

namespace App\Presenters;

use App\Models\Purchase;

/**
 * Презентация статуса покупки (метки и CSS-классы badge).
 * Вынесено из модели Purchase (presentation отделён от данных).
 */
class PurchasePresenter
{
    public static function statusText(?string $status): string
    {
        return match ($status) {
            Purchase::STATUS_PENDING => __('В обработке'),
            Purchase::STATUS_PROCESSING => __('В работе'),
            Purchase::STATUS_COMPLETED => __('Завершено'),
            Purchase::STATUS_FAILED => __('Ошибка'),
            Purchase::STATUS_CANCELLED => __('Отменено'),
            Purchase::STATUS_REFUNDED => __('Возврат'),
            default => (string) $status,
        };
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            Purchase::STATUS_PENDING => 'warning',
            Purchase::STATUS_PROCESSING => 'primary',
            Purchase::STATUS_COMPLETED => 'success',
            Purchase::STATUS_FAILED => 'danger',
            Purchase::STATUS_CANCELLED => 'secondary',
            Purchase::STATUS_REFUNDED => 'info',
            default => 'secondary',
        };
    }
}
