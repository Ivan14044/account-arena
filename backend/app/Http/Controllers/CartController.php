<?php

namespace App\Http\Controllers;

use App\Models\{Service, Subscription, Transaction, Option, Promocode, PromocodeUsage};
use App\Services\PromocodeValidationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request, PromocodeValidationService $promoService)
    {
        $request->validate([
            'services' => 'required|array',
            'services.*.id' => 'required|integer|exists:services,id',
            'services.*.subscription_type' => 'required|in:trial,premium',
            'payment_method' => 'required|in:credit_card,crypto,admin_bypass,free',
            'promocode' => 'nullable|string|required_if:payment_method,free',
        ]);

        $user = $this->getApiUser($request);

        // Проверяем, если payment_method = admin_bypass, то пользователь должен быть админом
        if ($request->payment_method === 'admin_bypass' && !$user->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Извлекаем ID сервисов и информацию о типах подписок
        $serviceIds = collect($request->services)->pluck('id')->toArray();
        $subscriptionTypes = collect($request->services)->pluck('subscription_type', 'id')->toArray();

        // Определяем, есть ли хотя бы один trial сервис
        $hasTrialServices = collect($request->services)->contains('subscription_type', 'trial');

        // Рассчитываем дату следующего платежа
        $nextPaymentDate = $hasTrialServices
            ? Carbon::now()->addDays(Option::get('trial_days'))
            : Carbon::now()->addMonth();

        $services = Service::find($serviceIds);

        // Validate promocode if provided (used for admin_bypass and free)
        $promoData = null;
        $promocodeParam = trim((string)$request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, optional($user)->id);
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? 'Invalid promocode'], 422);
            }
        }

        // payment_method: free — only allowed with free_access promo; force premium, 0.00 amounts; merge services from promo
        if ($request->payment_method === 'free') {
            if (!$promoData || ($promoData['type'] ?? '') !== 'free_access') {
                return response()->json(['success' => false, 'message' => 'Promocode is discount, not free access'], 422);
            }

            // Build final set of services: payload + services from promo
            $promoServiceIds = collect($promoData['services'] ?? [])->pluck('id')->all();
            $finalServiceIds = collect($serviceIds)->merge($promoServiceIds)->unique()->values()->all();
            $services = Service::find($finalServiceIds);

            $freeMap = collect($promoData['services'] ?? [])->keyBy('id');

            DB::transaction(function () use ($services, $user, $freeMap, $promoData) {
                foreach ($services as $service) {
                    $days = (int)($freeMap->get($service->id)['free_days'] ?? 0);

                    // Find existing subscription for this user/service
                    $existing = Subscription::where('user_id', $user->id)
                        ->where('service_id', $service->id)
                        ->orderByDesc('id')
                        ->first();

                    $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                        ? Carbon::parse($existing->next_payment_at)
                        : Carbon::now();

                    $nextAt = (clone $baseDate)->addDays(max(0, $days));

                    if ($existing) {
                        $existing->status = Subscription::STATUS_ACTIVE;
                        $existing->payment_method = 'free';
                        $existing->is_auto_renew = 0;
                        $existing->is_trial = 0;
                        $existing->next_payment_at = $nextAt;
                        $existing->save();
                        $subId = $existing->id;
                    } else {
                        $subscription = Subscription::create([
                            'user_id' => $user->id,
                            'status' => Subscription::STATUS_ACTIVE,
                            'payment_method' => 'free',
                            'service_id' => $service->id,
                            'is_auto_renew' => 0,
                            'is_trial' => 0,
                            'next_payment_at' => $nextAt,
                        ]);
                        $subId = $subscription->id;
                    }

                    Transaction::create([
                        'user_id' => $user->id,
                        'amount' => 0.00,
                        'currency' => Option::get('currency'),
                        'payment_method' => 'free',
                        'subscription_id' => $subId,
                    ]);
                }

                // Record usage
                $promo = Promocode::where('code', $promoData['code'] ?? '')->lockForUpdate()->first();
                if ($promo) {
                    PromocodeUsage::create([
                        'promocode_id' => $promo->id,
                        'user_id' => $user->id,
                        'order_id' => 'free_' . $user->id . '_' . time(),
                    ]);
                    if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                        $promo->usage_count = (int)$promo->usage_count + 1;
                        $promo->save();
                    }
                }
            });

            return response()->json(['success' => true]);
        }

        foreach ($services as $service) {
            $existing = Subscription::where('user_id', $user->id)
                ->where('service_id', $service->id)
                ->orderByDesc('id')
                ->first();

            $map = collect($promoData['services'] ?? [])->keyBy('id');
            $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                ? Carbon::parse($existing->next_payment_at)
                : $nextPaymentDate;

            $nextAt = ($promoData && ($promoData['type'] ?? '') === 'free_access' && $map->has($service->id))
                ? (clone $baseDate)->addDays(max(0, (int)$map->get($service->id)['free_days'] ?? 0))
                : $baseDate;

            if ($existing) {
                $existing->status = Subscription::STATUS_ACTIVE;
                $existing->payment_method = $request->payment_method;
                $existing->is_auto_renew = 0;
                $existing->is_trial = $hasTrialServices;
                $existing->next_payment_at = $nextAt;
                $existing->save();
                $subId = $existing->id;
            } else {
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'payment_method' => $request->payment_method,
                    'service_id' => $service->id,
                    'is_auto_renew' => 0,
                    'is_trial' => $hasTrialServices,
                    'next_payment_at' => $nextAt,
                ]);
                $subId = $subscription->id;
            }

            Transaction::create([
                'user_id' => $user->id,
                'amount' => ($promoData && ($promoData['type'] ?? '') === 'free_access' && $map->has($service->id)) ? 0.00 : $service->amount,
                'currency' => Option::get('currency'),
                'payment_method' => $request->payment_method,
                'subscription_id' => $subId,
            ]);
        }

        // Record usage if promo applied
        if ($promoData) {
            DB::transaction(function () use ($promoData, $user) {
                $promo = Promocode::where('code', $promoData['code'] ?? '')->lockForUpdate()->first();
                if ($promo) {
                    PromocodeUsage::create([
                        'promocode_id' => $promo->id,
                        'user_id' => $user->id,
                        'order_id' => 'free_' . $user->id . '_' . time(),
                    ]);

                    if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                        $promo->usage_count = (int)$promo->usage_count + 1;
                        $promo->save();
                    }
                }
            });
        }

        return response()->json(['success' => true]);
    }

    public function cancelSubscription(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required',
        ]);

        $subscription = Subscription::find($request->subscription_id);
        $subscription->update(['status' => Subscription::STATUS_CANCELED]);

        return response()->json(['success' => true]);
    }
}
