<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\WithdrawalRequest;
use App\Models\SupplierEarning;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    /**
     * Display withdrawal page with payment details and requests history.
     */
    public function index()
    {
        $supplier = auth()->user();

        // ВАЖНО: Синхронизируем баланс перед отображением
        $this->syncSupplierBalance($supplier);

        // Get available and held amounts from supplier_earnings
        $availableAmount = SupplierEarning::where('supplier_id', $supplier->id)
            ->where(function($q) {
                $q->where('status', 'available')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'held')
                         ->whereNotNull('available_at')
                         ->where('available_at', '<=', now());
                  });
            })->sum('amount');

        $heldAmount = SupplierEarning::where('supplier_id', $supplier->id)
            ->where('status', 'held')
            ->where(function($q) {
                $q->whereNull('available_at')
                  ->orWhere('available_at', '>', now());
            })->sum('amount');

        // Get withdrawal requests for this supplier
        $withdrawalRequests = WithdrawalRequest::where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        // Get telegram support link
        $telegramSupportLink = Option::get('telegram_support_link', 'https://t.me/support');

        return view('supplier.withdrawals.index', compact('supplier', 'withdrawalRequests', 'telegramSupportLink', 'availableAmount', 'heldAmount'));
    }

    /**
     * Show the form for updating payment details.
     */
    public function editPaymentDetails()
    {
        $supplier = auth()->user();
        return view('supplier.withdrawals.edit-payment-details', compact('supplier'));
    }

    /**
     * Update payment details.
     */
    public function updatePaymentDetails(Request $request)
    {
        $validated = $request->validate([
            'trc20_wallet' => ['nullable', 'string', 'max:255'],
            'card_number_uah' => ['nullable', 'string', 'max:255'],
        ]);

        $supplier = auth()->user();
        $supplier->update($validated);

        return redirect()->route('supplier.withdrawals.index')
            ->with('success', 'Реквизиты успешно обновлены!');
    }

    /**
     * Show the form for creating a new withdrawal request.
     */
    public function create()
    {
        $supplier = auth()->user();

        // Recompute available and held amounts for the form
        $availableAmount = SupplierEarning::where('supplier_id', $supplier->id)
            ->where(function($q) {
                $q->where('status', 'available')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'held')
                         ->whereNotNull('available_at')
                         ->where('available_at', '<=', now());
                  });
            })->sum('amount');

        $heldAmount = SupplierEarning::where('supplier_id', $supplier->id)
            ->where('status', 'held')
            ->where(function($q) {
                $q->whereNull('available_at')
                  ->orWhere('available_at', '>', now());
            })->sum('amount');

        // Check if supplier has payment details
        if (!$supplier->trc20_wallet && !$supplier->card_number_uah) {
            return redirect()->route('supplier.withdrawals.index')
                ->with('error', 'Сначала укажите реквизиты для вывода средств.');
        }

        // If there's nothing available to withdraw, prevent opening the create page
        if ($availableAmount <= 0) {
            return redirect()->route('supplier.withdrawals.index')
                ->with('error', 'Нет доступных средств для вывода. Дождитесь окончания холда или обратитесь к администратору.');
        }

        // Get telegram support link
        $telegramSupportLink = Option::get('telegram_support_link', 'https://t.me/support');

        return view('supplier.withdrawals.create', compact('supplier', 'telegramSupportLink', 'availableAmount', 'heldAmount'));
    }

    /**
     * Store a new withdrawal request.
     */
    public function store(Request $request)
    {
        $supplier = auth()->user();

        // Recompute available amount at the moment of request
        $availableAmount = SupplierEarning::where('supplier_id', $supplier->id)
            ->where(function($q) {
                $q->where('status', 'available')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'held')
                         ->whereNotNull('available_at')
                         ->where('available_at', '<=', now());
                  });
            })->sum('amount');

        // ВАЖНО: Вычитаем сумму уже созданных pending запросов на вывод
        // Это предотвращает создание нескольких запросов, сумма которых превышает доступную
        $pendingWithdrawals = WithdrawalRequest::where('supplier_id', $supplier->id)
            ->where('status', 'pending')
            ->sum('amount');
        
        $availableAmount = $availableAmount - $pendingWithdrawals;

        if ($availableAmount <= 0) {
            return redirect()->route('supplier.withdrawals.index')
                ->with('error', 'Нет доступных средств для вывода. Возможно, у вас уже есть запросы на вывод в обработке.');
        }

        $validated = $request->validate([
            'amount' => ['required','numeric','min:1','max:' . $availableAmount],
            'payment_method' => ['required', 'in:trc20,card_uah'],
        ]);

        // Check if supplier has the selected payment method
        if ($validated['payment_method'] == 'trc20' && !$supplier->trc20_wallet) {
            return back()->with('error', 'TRC-20 кошелек не указан в реквизитах.');
        }

        if ($validated['payment_method'] == 'card_uah' && !$supplier->card_number_uah) {
            return back()->with('error', 'Номер карты не указан в реквизитах.');
        }

        // Get payment details based on method
        $paymentDetails = $validated['payment_method'] == 'trc20'
            ? $supplier->trc20_wallet
            : $supplier->card_number_uah;

        WithdrawalRequest::create([
            'supplier_id' => $supplier->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_details' => $paymentDetails,
            'status' => 'pending',
        ]);

        return redirect()->route('supplier.withdrawals.index')
            ->with('success', 'Запрос на вывод средств успешно создан!');
    }

    /**
     * Cancel a pending withdrawal request.
     */
    public function cancel(WithdrawalRequest $withdrawal)
    {
        // Ensure the withdrawal belongs to the authenticated supplier
        if ($withdrawal->supplier_id !== auth()->id()) {
            abort(403);
        }

        // Can only cancel pending requests
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Можно отменить только запросы со статусом "В обработке".');
        }

        $withdrawal->update(['status' => 'rejected']);

        return back()->with('success', 'Запрос на вывод средств отменен.');
    }

    /**
     * Синхронизирует supplier_balance из SupplierEarning
     * Переводит средства из held в available и обновляет баланс
     */
    private function syncSupplierBalance($supplier)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($supplier) {
                // Находим earnings, готовые к переводу
                $readyToRelease = SupplierEarning::where('supplier_id', $supplier->id)
                    ->where('status', 'held')
                    ->whereNotNull('available_at')
                    ->where('available_at', '<=', now())
                    ->lockForUpdate()
                    ->get();

                if ($readyToRelease->isEmpty()) {
                    return; // Нет средств для перевода
                }

                $totalAmount = $readyToRelease->sum('amount');

                // Обновляем статус на 'available'
                $readyToRelease->each(function ($earning) {
                    $earning->update([
                        'status' => 'available',
                        'processed_at' => now(),
                    ]);
                });

                // Увеличиваем баланс поставщика
                $supplier->increment('supplier_balance', $totalAmount);

                \Illuminate\Support\Facades\Log::info('Supplier balance synced on withdrawal page', [
                    'supplier_id' => $supplier->id,
                    'earnings_count' => $readyToRelease->count(),
                    'amount_added' => $totalAmount,
                    'new_balance' => $supplier->fresh()->supplier_balance,
                ]);
            });
        } catch (\Throwable $e) {
            // Не ломаем загрузку страницы, но логируем ошибку
            \Illuminate\Support\Facades\Log::error('Failed to sync supplier balance on withdrawal page', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
