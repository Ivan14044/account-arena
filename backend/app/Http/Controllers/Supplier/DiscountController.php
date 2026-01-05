<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
        $supplier = auth()->user();
        
        $products = ServiceAccount::where('supplier_id', $supplier->id)
            ->where('discount_percent', '>', 0)
            ->orderByDesc('discount_percent')
            ->get();
        
        return view('supplier.discounts.index', compact('products'));
    }

    public function create()
    {
        $supplier = auth()->user();
        
        $products = ServiceAccount::where('supplier_id', $supplier->id)
            ->orderBy('title')
            ->get(['id', 'title', 'price']);
        
        return view('supplier.discounts.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:service_accounts,id'],
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:99'],
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date', 'after_or_equal:discount_start_date'],
        ]);

        $product = ServiceAccount::where('id', $validated['product_id'])
            ->where('supplier_id', auth()->id())
            ->firstOrFail();

        $product->update([
            'discount_percent' => $validated['discount_percent'],
            'discount_start_date' => $validated['discount_start_date'] ?? null,
            'discount_end_date' => $validated['discount_end_date'] ?? null,
        ]);

        return redirect()->route('supplier.discounts.index')
            ->with('success', 'Скидка успешно добавлена!');
    }

    public function edit(ServiceAccount $discount)
    {
        // Проверка принадлежности товара поставщику
        if ($discount->supplier_id !== auth()->id()) {
            abort(403);
        }

        $supplier = auth()->user();
        
        $products = ServiceAccount::where('supplier_id', $supplier->id)
            ->orderBy('title')
            ->get(['id', 'title', 'price']);

        return view('supplier.discounts.edit', compact('discount', 'products'));
    }

    public function update(Request $request, ServiceAccount $discount)
    {
        // Проверка принадлежности товара поставщику
        if ($discount->supplier_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:99'], // ВАЖНО: min:1, так как 0% не имеет смысла
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date', 'after_or_equal:discount_start_date'],
        ]);

        $discount->update($validated);

        return redirect()->route('supplier.discounts.index')
            ->with('success', 'Скидка успешно обновлена!');
    }

    public function destroy(ServiceAccount $discount)
    {
        // Проверка принадлежности товара поставщику
        if ($discount->supplier_id !== auth()->id()) {
            abort(403);
        }

        $discount->update([
            'discount_percent' => 0,
            'discount_start_date' => null,
            'discount_end_date' => null,
        ]);

        return redirect()->route('supplier.discounts.index')
            ->with('success', 'Скидка успешно удалена!');
    }
}

