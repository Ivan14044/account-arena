<?php

namespace App\Services;

use App\Models\User;

/**
 * Единый расчёт итоговой суммы заказа (скидки).
 *
 * Раньше эта логика была раскопирована по 6 местам (CartController,
 * GuestCartController, Mono/Cryptomus create*Payment), причём гостевые ветки
 * считали ИНАЧЕ: без кэпа 99% и с усечением процента через (int). Из-за этого
 * один и тот же промокод давал разные суммы гостю и авторизованному.
 *
 * Теперь все идут через computeOrderTotal() с единым поведением:
 *   итог = базовая_сумма * (1 - min(99, personal% + promo%)/100), округлённый
 *   до 0.01 и не ниже 0.01.
 */
class PricingService
{
    /** Максимальная суммарная скидка, % (итог всегда ≥ 1% базовой суммы). */
    public const MAX_DISCOUNT_PERCENT = 99;

    /** Минимальная сумма к оплате. */
    public const MIN_CHARGE = 0.01;

    /**
     * @param float       $baseTotal Сумма до скидок (сумма позиций).
     * @param User|null   $user      Покупатель (для персональной скидки); null для гостя.
     * @param array|null  $promoData Результат PromocodeValidationService::validate()
     *                               (ожидаются ключи 'type' и 'discount_percent').
     */
    public static function computeOrderTotal(float $baseTotal, ?User $user, ?array $promoData): float
    {
        $personalPercent = $user ? (float) $user->getActivePersonalDiscount() : 0.0;

        $promoPercent = 0.0;
        if ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $promoPercent = (float) ($promoData['discount_percent'] ?? 0);
        }

        $discountPercent = min(self::MAX_DISCOUNT_PERCENT, $personalPercent + $promoPercent);

        $total = $baseTotal;
        if ($discountPercent > 0) {
            $total -= $total * $discountPercent / 100;
        }

        return max(round($total, 2), self::MIN_CHARGE);
    }
}
