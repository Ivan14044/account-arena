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

        return view('admin.withdrawal-requests.index', compact('withdrawalRequests', 'suppliers'));
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

        $withdrawalRequest->update([
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Запрос на вывод средств одобрен.');
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

        // Deduct amount from supplier balance
        $supplier = $withdrawalRequest->supplier;
        if ($supplier->supplier_balance < $withdrawalRequest->amount) {
            return back()->with('error', 'Недостаточно средств на балансе поставщика.');
        }

        $supplier->decrement('supplier_balance', $withdrawalRequest->amount);

        $withdrawalRequest->update([
            'status' => 'paid',
            'admin_comment' => $validated['admin_comment'] ?? null,
            'processed_at' => now(),
        ]);

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
}
