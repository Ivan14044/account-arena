<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function cancelSubscription(Request $request)
    {
        $subscription = $this->getUserSubscription($request);
        if (!$subscription instanceof Subscription) {
            return $subscription;
        }

        $subscription->update(['status' => Subscription::STATUS_CANCELED]);

        return response()->json(['success' => true]);
    }

    public function toggleAutoRenew(Request $request)
    {
        $subscription = $this->getUserSubscription($request);
        if (!$subscription instanceof Subscription) {
            return $subscription;
        }

        $subscription->update([
            'is_auto_renew' => !$subscription->is_auto_renew,
        ]);

        return response()->json([
            'is_auto_renew' => !$subscription->is_auto_renew,
            'success' => true,
        ]);
    }

    /**
     * Проверка авторизации и получения подписки пользователя.
     */
    private function getUserSubscription(Request $request)
    {
        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->validate([
            'subscription_id' => 'required|integer',
        ]);

        $subscription = Subscription::find($request->subscription_id);

        if (!$subscription || $subscription->user_id !== $user->id) {
            return response()->json(['message' => 'Subscription does not belong to the authenticated user'], 401);
        }

        return $subscription;
    }
}
