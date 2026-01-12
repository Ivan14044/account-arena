<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

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

        $allRequests = WithdrawalRequest::all();
        $statistics = [
            'total' => $allRequests->count(),
            'pending' => $allRequests->where('status', 'pending')->count(),
            'paid' => $allRequests->where('status', 'paid')->count(),
            'total_amount' => $allRequests->where('status', 'paid')->sum('amount'),
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
    public function approve(WithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return back()->with('error', 'Можно одобрить только запросы со статусом "В обработке".');
        }

        // ВАЖНО: Синхронизируем баланс и проверяем доступность средств перед одобрением
        // Это предотвращает одобрение запросов, для которых недостаточно средств
        try {
            $supplier = \App\Models\User::find($withdrawalRequest->supplier_id);
            if (!$supplier) {
                return back()->with('error', 'Поставщик не найден.');
            }

            // Синхронизируем баланс (переводим held -> available -> supplier_balance)
            $this->syncSupplierBalance($supplier);
            $supplier->refresh();

            // Вычисляем доступную сумму с учетом pending запросов
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

            return back()->with('success', 'Запрос на вывод средств одобрен.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to approve withdrawal request', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Ошибка при одобрении запроса: ' . $e->getMessage());
        }
    }

    /**
     * Mark a withdrawal request as paid.
     */
    public function markAsPaid(WithdrawalRequest $withdrawalRequest, Request $request)
    {
        $validated = $request->validate([
            'admin_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($withdrawalRequest->status !== 'approved') {
            return back()->with('error', 'Можно отметить как оплаченный только одобренные запросы.');
        }

        // ВАЖНО: Используем транзакцию для атомарности операций
        \Illuminate\Support\Facades\DB::transaction(function () use ($withdrawalRequest, $validated) {
            $supplier = \App\Models\User::lockForUpdate()->find($withdrawalRequest->supplier_id);
            
            if (!$supplier) {
                throw new \Exception('Поставщик не найден');
            }

            // Сначала синхронизируем баланс (переводим held -> available -> supplier_balance)
            $this->syncSupplierBalance($supplier);
            
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

        return back()->with('success', 'Запрос на вывод средств отклонен.');
    }

    /**
     * Синхронизирует supplier_balance из SupplierEarning
     * Переводит средства из held в available и обновляет баланс
     */
    private function syncSupplierBalance($supplier)
    {
        try {
            // Находим earnings, готовые к переводу
            $readyToRelease = \App\Models\SupplierEarning::where('supplier_id', $supplier->id)
                ->where('status', 'held')
                ->whereNotNull('available_at')
                ->where('available_at', '<=', now())
                ->lockForUpdate()
                ->get();

            if ($readyToRelease->isEmpty()) {
                return; // Нет средств для перевода
            }

            $totalAmount = $readyToRelease->sum('amount');

            // ВАЖНО: Проверяем, что сумма положительная
            if ($totalAmount <= 0) {
                return; // Нет средств для перевода
            }

            // Обновляем статус на 'available'
            $readyToRelease->each(function ($earning) {
                $earning->update([
                    'status' => 'available',
                    'processed_at' => now(),
                ]);
            });

            // ВАЖНО: Проверяем, что баланс не станет отрицательным
            $currentBalance = $supplier->supplier_balance ?? 0;
            $newBalance = $currentBalance + $totalAmount;
            
            if ($newBalance < 0) {
                \Illuminate\Support\Facades\Log::error('Supplier balance sync: New balance would be negative', [
                    'supplier_id' => $supplier->id,
                    'current_balance' => $currentBalance,
                    'amount_to_add' => $totalAmount,
                    'new_balance' => $newBalance,
                ]);
                return; // Не обновляем баланс, если он станет отрицательным
            }

            // Увеличиваем баланс поставщика
            $supplier->increment('supplier_balance', $totalAmount);

            \Illuminate\Support\Facades\Log::info('Supplier balance synced in withdrawal approval', [
                'supplier_id' => $supplier->id,
                'earnings_count' => $readyToRelease->count(),
                'amount_added' => $totalAmount,
                'new_balance' => $supplier->fresh()->supplier_balance,
            ]);
        } catch (\Throwable $e) {
            // Логируем ошибку, но не прерываем процесс
            \Illuminate\Support\Facades\Log::error('Failed to sync supplier balance in withdrawal approval', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
