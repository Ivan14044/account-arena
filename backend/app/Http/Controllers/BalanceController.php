<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для управления балансом пользователя через API
 * 
 * Этот контроллер предоставляет API endpoints для:
 * - Получения текущего баланса
 * - Получения истории операций с балансом
 * - Проверки достаточности средств
 */
class BalanceController extends Controller
{
    /**
     * Сервис для работы с балансом
     */
    protected BalanceService $balanceService;

    /**
     * Конструктор контроллера
     */
    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Получить текущий баланс пользователя
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBalance(Request $request): JsonResponse
    {
        $user = $this->getApiUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        $balance = $this->balanceService->getBalance($user);
        $currency = \App\Models\Option::get('currency', 'USD');

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'currency' => $currency,
            'formatted' => number_format($balance, 2, '.', '') . ' ' . strtoupper($currency),
        ]);
    }

    /**
     * Получить историю операций с балансом
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $this->getApiUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        // Валидация параметров
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|string|in:topup_card,topup_crypto,topup_admin,topup_voucher,deduction,refund,purchase,adjustment',
            'status' => 'nullable|string|in:pending,completed,failed,cancelled',
        ]);

        $limit = $validated['limit'] ?? 50;
        
        // Получаем историю операций
        $query = \App\Models\BalanceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Применяем фильтры если указаны
        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $history = $query->get();

        return response()->json([
            'success' => true,
            'history' => $history->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'type_name' => $transaction->type_name,
                    'amount' => $transaction->amount,
                    'formatted_amount' => $transaction->formatted_amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'status' => $transaction->status,
                    'status_name' => $transaction->status_name,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->toIso8601String(),
                    'metadata' => $transaction->metadata,
                ];
            }),
            'total' => $history->count(),
        ]);
    }

    /**
     * Проверить достаточность средств для покупки
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkSufficientFunds(Request $request): JsonResponse
    {
        $user = $this->getApiUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        // Валидация суммы
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = round((float)$validated['amount'], 2);
        $currentBalance = $this->balanceService->getBalance($user);
        $hasFunds = $this->balanceService->hasSufficientFunds($user, $amount);

        return response()->json([
            'success' => true,
            'has_sufficient_funds' => $hasFunds,
            'current_balance' => $currentBalance,
            'required_amount' => $amount,
            'shortage' => $hasFunds ? 0 : round($amount - $currentBalance, 2),
        ]);
    }

    /**
     * Получить статистику по балансу пользователя
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = $this->getApiUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация'
            ], 401);
        }

        // Получаем статистику за последние 30 дней
        $thirtyDaysAgo = now()->subDays(30);
        
        $transactions = \App\Models\BalanceTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'completed')
            ->get();

        $totalTopUps = $transactions->whereIn('type', [
            'topup_card', 'topup_crypto', 'topup_admin', 'topup_voucher'
        ])->sum('amount');

        $totalDeductions = abs($transactions->whereIn('type', [
            'deduction', 'purchase'
        ])->sum('amount'));

        $totalRefunds = $transactions->where('type', 'refund')->sum('amount');

        return response()->json([
            'success' => true,
            'period' => '30_days',
            'statistics' => [
                'current_balance' => $this->balanceService->getBalance($user),
                'total_top_ups' => $totalTopUps,
                'total_deductions' => $totalDeductions,
                'total_refunds' => $totalRefunds,
                'transactions_count' => $transactions->count(),
                'top_ups_count' => $transactions->whereIn('type', [
                    'topup_card', 'topup_crypto', 'topup_admin', 'topup_voucher'
                ])->count(),
                'deductions_count' => $transactions->whereIn('type', [
                    'deduction', 'purchase'
                ])->count(),
            ],
            'currency' => \App\Models\Option::get('currency', 'USD'),
        ]);
    }
}


