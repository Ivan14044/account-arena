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

        $query = $user->transactions()
            ->with('subscription.service', 'serviceAccount');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'status' => $transaction->status ?? 'completed', // Default to completed for old records
                'service_name' => $transaction->subscription?->service?->name 
                    ?? $transaction->serviceAccount?->title 
                    ?? null,
                'created_at' => $transaction->created_at->toISOString(),
            ];
        });

        return response()->json($data);
    }
}

