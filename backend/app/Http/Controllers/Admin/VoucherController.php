<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\User;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::with('user')->orderBy('id', 'desc')->get();

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        return view('admin.vouchers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'code' => 'nullable|string|unique:vouchers,code',
            'note' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1|max:100',
        ]);

        $quantity = $request->input('quantity', 1);
        $vouchers = [];

        for ($i = 0; $i < $quantity; $i++) {
            $code = $request->input('code');
            if (!$code || ($i > 0)) {
                // Generate unique code
                do {
                    $code = Voucher::generateCode();
                } while (Voucher::where('code', $code)->exists());
            }

            $vouchers[] = Voucher::create([
                'code' => $code,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'note' => $validated['note'] ?? null,
                'is_active' => true,
            ]);
        }

        $message = $quantity === 1 
            ? 'Ваучер успешно создан.'
            : "Создано $quantity ваучеров.";

        return redirect()->route('admin.vouchers.index')->with('success', $message);
    }

    public function show(Voucher $voucher)
    {
        $voucher->load('user');
        return view('admin.vouchers.show', compact('voucher'));
    }

    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'code' => 'required|string|unique:vouchers,code,' . $voucher->id,
            'note' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $voucher->update($validated);

        return redirect()->route('admin.vouchers.index')->with('success', 'Ваучер успешно обновлен.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return redirect()->route('admin.vouchers.index')->with('success', 'Ваучер успешно удален.');
    }
}
