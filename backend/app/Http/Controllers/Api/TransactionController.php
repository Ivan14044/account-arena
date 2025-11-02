<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $transactions = $user->transactions()
            ->with('subscription.service')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'service_name' => $transaction->subscription?->service?->name ?? null,
                'created_at' => $transaction->created_at->toISOString(),
            ];
        });

        return response()->json($data);
    }
}

