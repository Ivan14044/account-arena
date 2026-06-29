<?php

namespace App\Presenters;

use App\Models\ProductDispute;

/**
 * Презентация претензии (тексты статуса/причины/решения и CSS-класс badge).
 * Вынесено из модели ProductDispute (presentation отделён от данных).
 */
class DisputePresenter
{
    public static function decisionText(?string $decision): string
    {
        return match ($decision) {
            ProductDispute::DECISION_REFUND => __('Возврат средств'),
            ProductDispute::DECISION_REPLACEMENT => __('Замена товара'),
            ProductDispute::DECISION_REJECTED => __('Отклонено'),
            default => __('Не обработано'),
        };
    }

    public static function reasonText(?string $reason): string
    {
        return match ($reason) {
            ProductDispute::REASON_INVALID_ACCOUNT => __('Невалидный аккаунт'),
            ProductDispute::REASON_WRONG_DATA => __('Неверные данные'),
            ProductDispute::REASON_NOT_WORKING => __('Не работает'),
            ProductDispute::REASON_ALREADY_USED => __('Уже использован'),
            ProductDispute::REASON_BANNED => __('Заблокирован'),
            ProductDispute::REASON_OTHER => __('Другое'),
            default => __('Не указано'),
        };
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            ProductDispute::STATUS_NEW => 'badge-warning',
            ProductDispute::STATUS_IN_REVIEW => 'badge-info',
            ProductDispute::STATUS_RESOLVED => 'badge-success',
            ProductDispute::STATUS_REJECTED => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public static function statusText(?string $status): string
    {
        return match ($status) {
            ProductDispute::STATUS_NEW => __('Новая'),
            ProductDispute::STATUS_IN_REVIEW => __('На рассмотрении'),
            ProductDispute::STATUS_RESOLVED => __('Решена'),
            ProductDispute::STATUS_REJECTED => __('Отклонена'),
            default => __('Неизвестно'),
        };
    }
}
