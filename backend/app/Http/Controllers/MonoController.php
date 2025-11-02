<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Option;
use App\Models\User;
use App\Services\NotificationTemplateService;
use App\Services\MonoPaymentService;
use App\Services\EmailService;
use App\Services\NotifierService;
use App\Services\PromocodeValidationService;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MonoController extends Controller
{
    public function createPayment(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        $request->validate([
            'services' => 'required|array|min:1',
            'services.*' => 'integer',
            'subscriptionTypes' => 'nullable',
            'promocode' => 'nullable|string',
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $services = Service::whereIn('id', $request->services)->get();
        if ($services->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Invalid services'], 422);
        }

        $subscriptionTypes = $request->subscriptionTypes ?? [];
        $trialIds = [];
        $totalAmount = 0;

        foreach ($services as $service) {
            $type = $subscriptionTypes[$service->id] ?? null;

            if ($type === 'trial') {
                $trialIds[] = $service->id;
                $totalAmount += $service->trial_amount ?? 0;
            } else {
                $totalAmount += $service->amount;
            }
        }

        $count = $services->count();
        $discount2 = (int) Option::get('discount_2', 0);
        $discount3 = (int) Option::get('discount_3', 0);

        $appliedDiscountPercent = 0;
        if ($count >= 3 && $discount3 > 0) {
            $appliedDiscountPercent = $discount3;
        } elseif ($count >= 2 && $discount2 > 0) {
            $appliedDiscountPercent = $discount2;
        }

        $originalAmount = round($totalAmount, 2);
        $discountAmount = $appliedDiscountPercent > 0
            ? round($originalAmount * $appliedDiscountPercent / 100, 2)
            : 0.00;

        // Apply promocode if provided
        $promoData = null;
        $promocodeParam = trim((string) $request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, $user->id);
            if (!($promoData['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $promoData['message'] ?? __('promocodes.invalid')], 422);
            }
        }

        // Per-service price map for applying promo free_access
        $serviceAmounts = [];
        foreach ($services as $service) {
            $type = $subscriptionTypes[$service->id] ?? null;
            $serviceAmounts[$service->id] = $type === 'trial' ? ($service->trial_amount ?? 0) : $service->amount;
        }

        if ($promoData && ($promoData['type'] ?? '') === 'free_access') {
            $freeMap = collect($promoData['services'] ?? [])->keyBy('id');
            foreach ($serviceAmounts as $sid => $amt) {
                if ($freeMap->has($sid)) {
                    $serviceAmounts[$sid] = 0.00; // free covered service
                }
            }
            $originalAmount = round(array_sum($serviceAmounts), 2);
            // Discount2/3 не применяется поверх free — но оставляем их как есть, т.к. суммы уже нули для covered
            $discountAmount = $appliedDiscountPercent > 0
                ? round($originalAmount * $appliedDiscountPercent / 100, 2)
                : 0.00;
        } elseif ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $promoPercent = (int)($promoData['discount_percent'] ?? 0);
            // Оба процента считаем от исходной суммы (как на фронте)
            $promoDiscount = $promoPercent > 0 ? round($originalAmount * $promoPercent / 100, 2) : 0.00;
            $discountAmount += $promoDiscount;
        }

        $totalAmount = round(max($originalAmount - $discountAmount, 0.01), 2);

        // Pack promo info for webhook
        $promoQuery = [];
        if ($promoData) {
            $promoQuery['promocode'] = $promoData['code'] ?? $promocodeParam;
            $promoQuery['promo_type'] = $promoData['type'] ?? '';
            if (($promoData['type'] ?? '') === 'free_access') {
                // encode map as id:days,id:days
                $pairs = collect($promoData['services'] ?? [])->map(function ($s) {
                    return ($s['id'] ?? 0) . ':' . ($s['free_days'] ?? 0);
                })->implode(',');
                $promoQuery['promo_free'] = $pairs;
            } else {
                $promoQuery['promo_percent'] = (int)($promoData['discount_percent'] ?? 0);
            }
        }

        $invoice = MonoPaymentService::createInvoice(
            amount: $totalAmount,
            redirectUrl: config('app.url') . '/checkout?success=true',
            webhookUrl: config('app.url') . '/api/mono/webhook?' . http_build_query(array_merge([
                'service_ids' => implode(',', $request->services),
                'trial_ids' => implode(',', $trialIds),
                'user_id' => $user->id,
            ], $promoQuery)),
            walletId: 'wallet_user_' . $user->id,
        );

        if (isset($invoice['pageUrl'])) {
            return response()->json(['success' => true, 'url' => $invoice['pageUrl']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create payment'], 422);
    }

    public function webhook(Request $request, PromocodeValidationService $promoService): JsonResponse
    {
        Log::info('Mono Webhook received', $request->all());

        if ($request->status !== 'success') {
            return response()->json(['success' => true]);
        }

        if ($this->invoiceAlreadyProcessed($request->invoiceId)) {
            return response()->json(['success' => false], 403);
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $trialIds = array_filter(array_map('intval', explode(',', $request->trial_ids ?? '')));
        $serviceIds = array_filter(array_map('intval', explode(',', $request->service_ids ?? '')));
        $services = Service::with('translations')->whereIn('id', $serviceIds)->get();
        $trialDays = (int)Option::get('trial_days', 7);
        $currency = strtoupper(Option::get('currency'));
        $totalAmount = 0;

        // Parse promo
        $promoCode = trim((string)($request->promocode ?? ''));
        $promoType = trim((string)($request->promo_type ?? '')); 
        $promoFreeRaw = trim((string)($request->promo_free ?? ''));
        $promoPercent = (int)($request->promo_percent ?? 0);
        $promoFreeMap = collect();
        if ($promoType === 'free_access' && $promoFreeRaw !== '') {
            $promoFreeMap = collect(explode(',', $promoFreeRaw))->mapWithKeys(function ($pair) {
                [$sid, $days] = array_pad(explode(':', $pair), 2, 0);
                return [(int)$sid => (int)$days];
            });
        }

        foreach ($services as $service) {
            $isTrial = in_array($service->id, $trialIds, true);
            $amount = $isTrial ? ($service->trial_amount ?? 0) : $service->amount;
            // Apply promo on webhook: amount 0 for free_access covered
            if ($promoType === 'free_access' && $promoFreeMap->has($service->id)) {
                $amount = 0.00;
            }

            // Upsert subscription: extend existing or create new
            $existing = Subscription::where('user_id', $user->id)
                ->where('service_id', $service->id)
                ->orderByDesc('id')
                ->first();

            $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                ? Carbon::parse($existing->next_payment_at)
                : Carbon::now();

            if ($isTrial) {
                $nextAt = (clone $baseDate)->addDays($trialDays);
            } elseif ($promoType === 'free_access' && $promoFreeMap->has($service->id)) {
                $nextAt = (clone $baseDate)->addDays(max(0, (int)$promoFreeMap->get($service->id)));
            } else {
                $nextAt = (clone $baseDate)->addMonth();
            }

            if ($existing) {
                $existing->status = Subscription::STATUS_ACTIVE;
                $existing->payment_method = 'credit_card';
                $existing->is_auto_renew = 1;
                $existing->is_trial = $isTrial;
                $existing->next_payment_at = $nextAt;
                $existing->order_id = $request->invoiceId;
                $existing->save();
                $subId = $existing->id;
                $notifyDate = Carbon::parse($existing->next_payment_at);
            } else {
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'payment_method' => 'credit_card',
                    'service_id' => $service->id,
                    'is_auto_renew' => 1,
                    'next_payment_at' => $nextAt,
                    'is_trial' => $isTrial,
                    'order_id' => $request->invoiceId,
                ]);
                $subId = $subscription->id;
                $notifyDate = $subscription->next_payment_at;
            }

            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => 'credit_card',
                'subscription_id' => $subId,
            ]);

            $totalAmount += $amount;

            $this->notifyUserOnSubscription($user, $service, Carbon::parse($notifyDate));
        }

        EmailService::send('payment_confirmation', $user->id, [
            'amount' => number_format($totalAmount, 2, '.', '') . ' ' . $currency,
        ]);

        NotifierService::send(
            'payment',
            __('notifier.new_payment_title', array(
                'method' => 'Mono'
            )),
            __('notifier.new_payment_message', array(
                'method' => 'Mono',
                'email' => $user->email,
                'name' => $user->name
            ))
        );

        // Record promocode usage if present
        if ($promoCode !== '') {
            DB::transaction(function () use ($promoCode, $user, $request) {
                $promo = Promocode::where('code', $promoCode)->lockForUpdate()->first();
                if ($promo) {
                    PromocodeUsage::create([
                        'promocode_id' => $promo->id,
                        'user_id' => $user->id,
                        'order_id' => (string)$request->invoiceId,
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

    private function invoiceAlreadyProcessed(string $invoiceId): bool
    {
        return Subscription::where('order_id', $invoiceId)->exists();
    }

    private function notifyUserOnSubscription(User $user, Service $service, Carbon $nextPaymentDate): void
    {
        app(NotificationTemplateService::class)->sendToUser($user, 'purchase', [
            'service' => $service->code,
            'date' => $nextPaymentDate->format('d.m.Y'),
        ]);

        $serviceName = $service->getTranslation('name', $user->lang ?? 'en') ?? env('APP_NAME');

        EmailService::send('subscription_activated', $user->id, [
            'service_name' => $serviceName,
        ]);
        NotifierService::send(
            'subscription_activated',
            __('notifier.new_subscription_title', array(
                'method' => 'Mono'
            )),
            __('notifier.new_subscription_message', array(
                'method' => 'Mono',
                'email' => $user->email,
                'name' => $user->name
            ))
        );
    }
}
