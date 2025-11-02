<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Option;
use App\Models\User;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Services\NotificationTemplateService;
use App\Services\NotifierService;
use Carbon\Carbon;
use Cryptomus\Api\RequestBuilderException;
use FunnyDev\Cryptomus\CryptomusSdk;
use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\PromocodeValidationService;
use Illuminate\Support\Facades\DB;

class CryptomusController extends Controller
{
    public function createPayment(Request $request, PromocodeValidationService $promoService)
    {
        $request->validate([
            'services' => 'required|array|min:1',
            'services.*' => 'integer',
            'promocode' => 'nullable|string',
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $services = Service::whereIn('id', $request->services)->get();
        if ($services->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid services',
            ], 422);
        }

        $originalAmount = $services->sum('amount');

        $discount2 = (int)Option::get('discount_2', 0);
        $discount3 = (int)Option::get('discount_3', 0);

        $count = $services->count();

        $appliedDiscountPercent = 0;
        if ($count >= 3 && $discount3 > 0) {
            $appliedDiscountPercent = $discount3;
        } elseif ($count >= 2 && $discount2 > 0) {
            $appliedDiscountPercent = $discount2;
        }

        // Apply promocode if provided
        $promoData = null;
        $promocodeParam = trim((string) $request->promocode);
        if ($promocodeParam !== '') {
            $promoData = $promoService->validate($promocodeParam, $user->id);

            if (!($promoData['ok'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $promoData['message'] ?? __('promocodes.invalid')
                ], 422);
            }
        }

        $discountAmount = round($originalAmount * $appliedDiscountPercent / 100, 2);

        if ($promoData && ($promoData['type'] ?? '') === 'free_access') {
            $freeMap = collect($promoData['services'] ?? [])->keyBy('id');
            $originalAmount = round($services->sum(function ($s) use ($freeMap) {
                return $freeMap->has($s->id) ? 0.00 : $s->amount;
            }), 2);

            $discountAmount = round($originalAmount * $appliedDiscountPercent / 100, 2);
        } elseif ($promoData && ($promoData['type'] ?? '') === 'discount') {
            $promoPercent = (int)($promoData['discount_percent'] ?? 0);
            // Оба процента считаем от исходной суммы (как на фронте)
            $promoDiscount = $promoPercent > 0 ? round($originalAmount * $promoPercent / 100, 2) : 0.00;
            $discountAmount += $promoDiscount;
        }

        $totalAmount = round($originalAmount - $discountAmount, 2);

        if ($totalAmount <= 0) {
            $totalAmount = 0.01;
        }

        $orderId = 'order_' . $user->id . '_' . time();
        $sdk = new CryptomusSdk();

        $promoQuery = [];
        if ($promoData) {
            $promoQuery['promocode'] = $promoData['code'] ?? $promocodeParam;
            $promoQuery['promo_type'] = $promoData['type'] ?? '';
            if (($promoData['type'] ?? '') === 'free_access') {
                $pairs = collect($promoData['services'] ?? [])->map(function ($s) {
                    return ($s['id'] ?? 0) . ':' . ($s['free_days'] ?? 0);
                })->implode(',');
                $promoQuery['promo_free'] = $pairs;
            } else {
                $promoQuery['promo_percent'] = (int)($promoData['discount_percent'] ?? 0);
            }
        }

        $response = $sdk->create_payment(
            $orderId,
            $totalAmount,
            Option::get('currency'),
            '',
            '',
            config('app.url') . '/checkout',
            config('app.url') . '/api/cryptomus/webhook?service_ids=' . implode(',', $request->services) . '&user_id=' . $user->id . (count($promoQuery) ? '&' . http_build_query($promoQuery) : ''),
            config('app.url') . '/checkout?success=true',
        );

        if ($response) {
            return response()->json([
                'success' => true,
                'url' => $response,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to create payment',
        ], 422);
    }

    /**
     * @throws RequestBuilderException
     */
    public function webhook(Request $request)
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);

        \Log::info('Webhook received', $data);

        if (!is_array($data)) {
            \Log::error('Invalid JSON');
            return response('Invalid JSON', 400);
        }

        $cryptomus = new CryptomusSdk();
        $result = $cryptomus->read_result($data);
        \Log::info('Webhook check - ', $result);

        /*
         * You could handle the response of transaction here like:
         * if ($result['status']) {approve order for use or email them...} else {notice them the $result['message']}
         * if $result['message'] is "Trying to fake payment result" then you should block your user!
         * You could get 2 integer variables Session::get('cryptomus_hacked') & Session::get('cryptomus_hacked') to decide what to do with your user.
         */

        if ($result['status'] == 'paid') {
            if (Subscription::where('order_id', $data['order_id'])->exists()) {
                return response('OK', 200);
            }

            $nextPaymentDate = Carbon::now()->addMonth();
            $user = User::find($request->user_id);
            $totalAmount = 0;
            $serviceIds = explode(',', $request->service_ids);
            $services = Service::with('translations')->whereIn('id', $serviceIds)->get();

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
                $existing = Subscription::where('user_id', $user->id)
                    ->where('service_id', $service->id)
                    ->orderByDesc('id')
                    ->first();

                $baseDate = $existing && $existing->next_payment_at && Carbon::parse($existing->next_payment_at)->gt(Carbon::now())
                    ? Carbon::parse($existing->next_payment_at)
                    : $nextPaymentDate;

                $nextAt = ($promoType === 'free_access' && $promoFreeMap->has($service->id))
                    ? (clone $baseDate)->addDays(max(0, (int)$promoFreeMap->get($service->id)))
                    : $baseDate;

                if ($existing) {
                    $existing->status = Subscription::STATUS_ACTIVE;
                    $existing->payment_method = 'crypto';
                    $existing->is_auto_renew = 0;
                    $existing->next_payment_at = $nextAt;
                    $existing->order_id = $data['order_id'];
                    $existing->save();
                    $subId = $existing->id;
                } else {
                    $subscription = Subscription::create([
                        'user_id' => $user->id,
                        'status' => Subscription::STATUS_ACTIVE,
                        'payment_method' => 'crypto',
                        'service_id' => $service->id,
                        'is_auto_renew' => 0,
                        'next_payment_at' => $nextAt,
                        'order_id' => $data['order_id']
                    ]);
                    $subId = $subscription->id;
                }

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => ($promoType === 'free_access' && $promoFreeMap->has($service->id)) ? 0.00 : $service->amount,
                    'currency' => Option::get('currency'),
                    'payment_method' => 'crypto',
                    'subscription_id' => $subId
                ]);

                $totalAmount += $service->amount;

                app(NotificationTemplateService::class)->sendToUser($user, 'purchase', [
                    'service' => $service->code,
                    'date' => $nextPaymentDate->format('d.m.Y'),
                ]);

                $serviceName = $service?->getTranslation('name', $user->lang ?? 'en') ?? $service?->name;

                EmailService::send('subscription_activated', $user->id, [
                    'service_name' => $serviceName
                ]);
            }

            EmailService::send('payment_confirmation', $user->id, [
                'amount' => number_format($totalAmount, 2, '.', '') . ' ' . strtoupper(Option::get('currency'))
            ]);

            NotifierService::send(
                'payment',
                __('notifier.new_payment_title', array(
                    'method' => 'Crypto'
                )),
                __('notifier.new_payment_message', array(
                    'method' => 'Crypto',
                    'email' => $user->email,
                    'name' => $user->name
                ))
            );
            // Record promocode usage if present
            if ($promoCode !== '') {
                DB::transaction(function () use ($promoCode, $user, $data) {
                    $promo = Promocode::where('code', $promoCode)->lockForUpdate()->first();
                    if ($promo) {
                        PromocodeUsage::create([
                                'promocode_id' => $promo->id,
                                'user_id' => $user->id,
                                'order_id' => (string)($data['order_id'] ?? ''),
                        ]);
                        
                        if ((int)$promo->usage_limit > 0 && (int)$promo->usage_count < (int)$promo->usage_limit) {
                            $promo->usage_count = (int)$promo->usage_count + 1;
                            $promo->save();
                        }
                    }
                });
            }
        }

        return response('OK', 200);
    }
}
