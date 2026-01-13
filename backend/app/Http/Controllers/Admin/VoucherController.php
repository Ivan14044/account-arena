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
        $statistics = [
            'total' => Voucher::count(),
            'active' => Voucher::where('is_active', true)->whereNull('used_at')->count(),
            'used' => Voucher::whereNotNull('used_at')->count(),
            'total_amount' => Voucher::sum('amount'),
        ];

        $vouchers = Voucher::with('user')->orderBy('id', 'desc')->paginate(20);

        return view('admin.vouchers.index', compact('vouchers', 'statistics'));
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($quantity, $validated, $request) {
            $existingCodes = Voucher::pluck('code')->toArray();
            $vouchersToInsert = [];
            $codesInBatch = [];

            for ($i = 0; $i < $quantity; $i++) {
                $code = $request->input('code');
                if (!$code || ($i > 0)) {
                    do {
                        $code = Voucher::generateCode();
                    } while (in_array($code, $existingCodes) || in_array($code, $codesInBatch));
                }
                $codesInBatch[] = $code;

                $vouchersToInsert[] = [
                    'code' => $code,
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'note' => $validated['note'] ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Voucher::insert($vouchersToInsert);
        });

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
