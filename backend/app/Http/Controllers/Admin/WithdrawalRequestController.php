<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Models\SupplierNotification;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalRequestController extends Controller
{
    /**
     * Display a listing of withdrawal requests.
     */
    public function index(Request $request)
    {
        $query = WithdrawalRequest::with('supplier');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $withdrawalRequests = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Get all suppliers for filter
        $suppliers = \App\Models\User::where('is_supplier', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $statistics = [
            'total' => WithdrawalRequest::count(),
            'pending' => WithdrawalRequest::where('status', 'pending')->count(),
            'paid' => WithdrawalRequest::where('status', 'paid')->count(),
            'total_amount' => WithdrawalRequest::where('status', 'paid')->sum('amount'),
        ];

        return view('admin.withdrawal-requests.index', compact('withdrawalRequests', 'suppliers', 'statistics'));
    }

    /**
     * Display the specified withdrawal request.
     */
    public function show(WithdrawalRequest $withdrawalRequest)
    {
        $withdrawalRequest->load('supplier');
        return view('admin.withdrawal-requests.show', compact('withdrawalRequest'));
    }

    /**
     * Approve a withdrawal request.
     */
    public function approve(WithdrawalRequest $withdrawalRequest, BalanceService $balanceService)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return back()->with('error', 'Можно одобрить только запросы со статусом "В обработке".');
        }

        // ВАЖНО: Используем транзакцию и блокировку пользователя для предотвращения Race Condition
        return \Illuminate\Support\Facades\DB::transaction(function () use ($withdrawalRequest, $balanceService) {
            try {
                // Блокируем запись поставщика для эксклюзивного доступа к расчетам баланса
                $supplier = \App\Models\User::lockForUpdate()->find($withdrawalRequest->supplier_id);
                
                if (!$supplier) {
                    return back()->with('error', 'Поставщик не найден.');
                }

                // Синхронизируем баланс (переводим held -> available -> supplier_balance)
                // Теперь это происходит через централизованный сервис
                $balanceService->syncSupplierBalance($supplier);
                $supplier->refresh();

                // Вычисляем доступную сумму с учетом других pending запросов
                $availableAmount = \App\Models\SupplierEarning::where('supplier_id', $supplier->id)
                    ->where(function($q) {
                        $q->where('status', 'available')
                          ->orWhere(function($q2) {
                              $q2->where('status', 'held')
                                 ->whereNotNull('available_at')
                                 ->where('available_at', '<=', now());
                          });
                    })->sum('amount');

                // Вычитаем сумму других pending запросов (кроме текущего)
                $pendingWithdrawals = WithdrawalRequest::where('supplier_id', $supplier->id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $withdrawalRequest->id)
                    ->sum('amount');
                
                $availableAmount = max(0, $availableAmount - $pendingWithdrawals);

                if ($availableAmount < $withdrawalRequest->amount) {
                    \Illuminate\Support\Facades\Log::warning('Withdrawal request approval rejected: insufficient funds', [
                        'withdrawal_request_id' => $withdrawalRequest->id,
                        'supplier_id' => $supplier->id,
                        'requested_amount' => $withdrawalRequest->amount,
                        'available_amount' => $availableAmount,
                    ]);
                    return back()->with('error', 'Недостаточно средств для одобрения запроса. Доступно: ' . number_format($availableAmount, 2) . ' USD, запрошено: ' . number_format($withdrawalRequest->amount, 2) . ' USD');
                }

                $withdrawalRequest->update([
                    'status' => 'approved',
                    'processed_at' => now(),
                ]);

                \Illuminate\Support\Facades\Log::info('Withdrawal request approved', [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'supplier_id' => $supplier->id,
                    'amount' => $withdrawalRequest->amount,
                    'available_amount' => $availableAmount,
                ]);

                // ВАЖНО: Уведомляем поставщика об одобрении запроса
                try {
                    SupplierNotification::create([
                        'user_id' => $supplier->id,
                        'type' => 'withdrawal_approved',
                        'title' => 'Запрос на вывод одобрен',
                        'message' => "Ваш запрос на вывод средств в размере " . number_format($withdrawalRequest->amount, 2) . " USD одобрен и будет оплачен в ближайшее время.",
                        'data' => [
                            'withdrawal_id' => $withdrawalRequest->id,
                            'amount' => $withdrawalRequest->amount,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send supplier notification (withdrawal approved)', [
                        'withdrawal_id' => $withdrawalRequest->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return back()->with('success', 'Запрос на вывод средств одобрен.');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to approve withdrawal request', [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e; // Пробрасываем для отката транзакции
            }
        });
    }

    /**
     * Mark a withdrawal request as paid.
     */
    public function markAsPaid(WithdrawalRequest $withdrawalRequest, Request $request, BalanceService $balanceService)
    {
        $validated = $request->validate([
            'admin_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($withdrawalRequest->status !== 'approved') {
            return back()->with('error', 'Можно отметить как оплаченный только одобренные запросы.');
        }

        // ВАЖНО: Используем транзакцию для атомарности операций
        \Illuminate\Support\Facades\DB::transaction(function () use ($withdrawalRequest, $validated, $balanceService) {
            $supplier = \App\Models\User::lockForUpdate()->find($withdrawalRequest->supplier_id);
            
            if (!$supplier) {
                throw new \Exception('Поставщик не найден');
            }

            // Сначала синхронизируем баланс (переводим held -> available -> supplier_balance)
            $balanceService->syncSupplierBalance($supplier);
            
            // Обновляем объект поставщика после синхронизации
            $supplier->refresh();

            // Проверяем баланс поставщика (который должен быть синхронизирован)
            if ($supplier->supplier_balance < $withdrawalRequest->amount) {
                throw new \Exception('Недостаточно средств на балансе поставщика. Доступно: ' . number_format($supplier->supplier_balance, 2) . ' USD');
            }

            // Списываем с баланса поставщика
            $supplier->decrement('supplier_balance', $withdrawalRequest->amount);

            // Обновляем статус SupplierEarning на 'withdrawn' для учета выведенных средств
            // Списываем по принципу FIFO (первыми выводим самые старые доступные earnings)
            $remainingAmount = $withdrawalRequest->amount;
            $earningsToWithdraw = \App\Models\SupplierEarning::where('supplier_id', $supplier->id)
                ->where('status', 'available')
                ->orderBy('available_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($earningsToWithdraw as $earning) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $amountToDeduct = min($earning->amount, $remainingAmount);
                $remainingAmount -= $amountToDeduct;

                // Если списываем полностью - меняем статус на withdrawn
                if ($amountToDeduct >= $earning->amount) {
                    $earning->update([
                        'status' => 'withdrawn',
                        'processed_at' => now(),
                    ]);
                } else {
                    // Если частично - создаем новую запись для остатка
                    // ВАЖНО: available_at устанавливаем в null или now(), так как это новая запись
                    // и она уже должна быть доступна (средства уже переведены в supplier_balance)
                    \App\Models\SupplierEarning::create([
                        'supplier_id' => $earning->supplier_id,
                        'purchase_id' => $earning->purchase_id,
                        'transaction_id' => $earning->transaction_id,
                        'amount' => $earning->amount - $amountToDeduct,
                        'status' => 'available',
                        'available_at' => now(), // Устанавливаем текущее время, так как средства уже доступны
                    ]);

                    // Текущую запись помечаем как withdrawn
                    $earning->update([
                        'amount' => $amountToDeduct,
                        'status' => 'withdrawn',
                        'processed_at' => now(),
                    ]);
                }
            }

            // Обновляем запрос на вывод
            $withdrawalRequest->update([
                'status' => 'paid',
                'admin_comment' => $validated['admin_comment'] ?? null,
                'processed_at' => now(),
            ]);

            \Illuminate\Support\Facades\Log::info('Supplier withdrawal paid', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'supplier_id' => $supplier->id,
                'amount' => $withdrawalRequest->amount,
                'new_balance' => $supplier->fresh()->supplier_balance,
            ]);

            // ВАЖНО: Уведомляем поставщика об оплате
            try {
                SupplierNotification::create([
                    'user_id' => $supplier->id,
                    'type' => 'withdrawal_paid',
                    'title' => 'Выплата произведена',
                    'message' => "Ваш запрос на вывод средств в размере " . number_format($withdrawalRequest->amount, 2) . " USD успешно оплачен.",
                    'data' => [
                        'withdrawal_id' => $withdrawalRequest->id,
                        'amount' => $withdrawalRequest->amount,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send supplier notification (withdrawal paid)', [
                    'withdrawal_id' => $withdrawalRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return back()->with('success', 'Запрос отмечен как оплаченный. Баланс поставщика обновлен.');
    }

    /**
     * Reject a withdrawal request.
     */
    public function reject(WithdrawalRequest $withdrawalRequest, Request $request)
    {
        $validated = $request->validate([
            'admin_comment' => ['required', 'string', 'max:1000'],
        ]);

        if ($withdrawalRequest->status === 'paid') {
            return back()->with('error', 'Нельзя отклонить уже оплаченный запрос.');
        }

        $withdrawalRequest->update([
            'status' => 'rejected',
            'admin_comment' => $validated['admin_comment'],
            'processed_at' => now(),
        ]);

        // ВАЖНО: Уведомляем поставщика об отказе
        try {
            SupplierNotification::create([
                'user_id' => $withdrawalRequest->supplier_id,
                'type' => 'withdrawal_rejected',
                'title' => 'Запрос на вывод отклонен',
                'message' => "Ваш запрос на вывод средств в размере " . number_format($withdrawalRequest->amount, 2) . " USD был отклонен администратором. Причина: " . $validated['admin_comment'],
                'data' => [
                    'withdrawal_id' => $withdrawalRequest->id,
                    'amount' => $withdrawalRequest->amount,
                    'reason' => $validated['admin_comment'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send supplier notification (withdrawal rejected)', [
                'withdrawal_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Запрос на вывод средств отклонен.');
    }
}
