<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    /**
     * Display withdrawal page with payment details and requests history.
     */
    public function index()
    {
        $supplier = auth()->user();
        
        // Get withdrawal requests for this supplier
        $withdrawalRequests = WithdrawalRequest::where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->paginate(10);
        
        // Get telegram support link
        $telegramSupportLink = Option::get('telegram_support_link', 'https://t.me/support');
        
        return view('supplier.withdrawals.index', compact('supplier', 'withdrawalRequests', 'telegramSupportLink'));
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
        
        // Check if supplier has payment details
        if (!$supplier->trc20_wallet && !$supplier->card_number_uah) {
            return redirect()->route('supplier.withdrawals.index')
                ->with('error', 'Сначала укажите реквизиты для вывода средств.');
        }
        
        // Get telegram support link
        $telegramSupportLink = Option::get('telegram_support_link', 'https://t.me/support');
        
        return view('supplier.withdrawals.create', compact('supplier', 'telegramSupportLink'));
    }

    /**
     * Store a new withdrawal request.
     */
    public function store(Request $request)
    {
        $supplier = auth()->user();
        
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $supplier->supplier_balance],
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
}
