<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\User;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $query = User::where('is_supplier', true);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate(20)->withQueryString();

        $allSuppliers = User::where('is_supplier', true)->get();
        $statistics = [
            'total' => $allSuppliers->count(),
            'active' => $allSuppliers->where('is_blocked', false)->count(),
            'total_balance' => $allSuppliers->sum('supplier_balance'),
        ];

        return view('admin.suppliers.index', compact('suppliers', 'statistics'));
    }

    /**
     * Display the specified supplier.
     */
    public function show(User $supplier)
    {
        if (!$supplier->is_supplier) {
            abort(404);
        }

        $supplier->load(['supplierProducts', 'withdrawalRequests']);
        
        return view('admin.suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing supplier settings.
     */
    public function settings()
    {
        $telegramSupportLink = Option::get('telegram_support_link', 'https://t.me/support');
        
        return view('admin.suppliers.settings', compact('telegramSupportLink'));
    }

    /**
     * Update supplier settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'telegram_support_link' => ['required', 'url', 'max:255'],
        ]);

        Option::set('telegram_support_link', $validated['telegram_support_link']);

        return back()->with('success', 'Настройки успешно обновлены!');
    }
}
