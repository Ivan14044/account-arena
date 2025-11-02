<?php

namespace App\Services;

use App\Models\Promocode;
use App\Models\Service;

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

        $promocode = Promocode::with(['services.translations'])->where('code', $code)->first();
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
            $services = $promocode->services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->getTranslation('name', 'en') ?? $service->admin_name ?? ('Service #' . $service->id),
                    'free_days' => (int)($service->pivot->free_days ?? 0),
                ];
            })->values()->all();
            $payload['services'] = $services;
        }

        return $payload;
    }
}


