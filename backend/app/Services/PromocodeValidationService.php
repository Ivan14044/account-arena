<?php

namespace App\Services;

use App\Models\Promocode;

class PromocodeValidationService
{
    public function validate(string $code, ?int $userId = null): array
    {
        $code = trim($code);
        if ($code === '') {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => __('promocodes.code_required'),
            ];
        }

        $promocode = Promocode::where('code', $code)->first();
        if (!$promocode) {
            return [
                'ok' => false,
                'status' => 'not_found',
                'message' => __('promocodes.not_found'),
            ];
        }

        $now = now();
        $paused = !$promocode->is_active;
        $expired = $promocode->expires_at && $promocode->expires_at->lt($now);
        $scheduled = $promocode->starts_at && $promocode->starts_at->gt($now);
        $exhausted = ((int)($promocode->usage_limit ?? 0)) > 0
            && ((int)($promocode->usage_count ?? 0)) >= (int)$promocode->usage_limit;

        $status = 'active';
        $message = null;
        if ($paused) { $status = 'paused'; $message = __('promocodes.paused'); }
        elseif ($expired) { $status = 'expired'; $message = __('promocodes.expired'); }
        elseif ($exhausted) { $status = 'exhausted'; $message = __('promocodes.exhausted'); }
        elseif ($scheduled) { $status = 'scheduled'; $message = __('promocodes.scheduled'); }

        if ($status !== 'active') {
            return [
                'ok' => false,
                'status' => $status,
                'message' => $message,
            ];
        }

        // Per-user limit check if provided
        // ПРИМЕЧАНИЕ: Финальная проверка per_user_limit выполняется при записи использования
        // с блокировкой (lockForUpdate) в контроллерах для предотвращения race condition
        if ($userId && (int)($promocode->per_user_limit ?? 0) > 0) {
            $usedCount = \DB::table('promocode_usages')
                ->where('promocode_id', $promocode->id)
                ->where('user_id', $userId)
                ->count();
            if ($usedCount >= (int)$promocode->per_user_limit) {
                return [
                    'ok' => false,
                    'status' => 'per_user_limit',
                    'message' => __('promocodes.per_user_limit'),
                ];
            }
        }
        
        // ПРИМЕЧАНИЕ: Для гостей (userId = null) per_user_limit не проверяется
        // Это может позволить гостю использовать промокод несколько раз
        // Если требуется ограничение для гостей, нужно добавить проверку по email или IP

        $payload = [
            'ok' => true,
            'status' => 'active',
            'type' => $promocode->type,
            'code' => $promocode->code,
            'promocode_id' => $promocode->id,
        ];

        if ($promocode->type === 'discount') {
            $payload['discount_percent'] = (int)$promocode->percent_discount;
        } else {
            // Services are no longer supported - free_access type is not available
            $payload['services'] = [];
        }

        return $payload;
    }
}


