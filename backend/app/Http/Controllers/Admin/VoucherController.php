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
            'quantity' => 'nullable|integer|min:1|max:500',
        ]);

        $quantity = $request->input('quantity', 1);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($quantity, $validated, $request) {
                $vouchersToInsert = [];
                $generatedCodes = [];
                $maxAttempts = 3;
                $attempt = 0;
                
                $targetQuantity = $quantity;
                $currentInserted = 0;

                while ($currentInserted < $targetQuantity && $attempt < $maxAttempts) {
                    $attempt++;
                    $batchToGenerate = $targetQuantity - $currentInserted;
                    $batchGeneratedCodes = [];
                    $batchVouchers = [];

                    for ($i = 0; $i < $batchToGenerate; $i++) {
                        $code = ($currentInserted === 0 && $i === 0 && $request->filled('code')) ? $request->input('code') : null;
                        
                        if (!$code) {
                            $code = Voucher::generateCode();
                        }
                        
                        if (isset($generatedCodes[$code]) || isset($batchGeneratedCodes[$code])) {
                            $i--;
                            continue;
                        }
                        
                        $batchGeneratedCodes[$code] = true;
                        $batchVouchers[] = [
                            'code' => $code,
                            'amount' => $validated['amount'],
                            'currency' => $validated['currency'],
                            'note' => $validated['note'] ?? null,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Проверяем всю пачку в БД
                    $codes = array_keys($batchGeneratedCodes);
                    $existingCodes = Voucher::withTrashed()->whereIn('code', $codes)->pluck('code')->toArray();
                    $existingMap = array_flip($existingCodes);

                    foreach ($batchVouchers as $voucher) {
                        if (!isset($existingMap[$voucher['code']])) {
                            $vouchersToInsert[] = $voucher;
                            $generatedCodes[$voucher['code']] = true;
                            $currentInserted++;
                        }
                    }
                }

                if ($currentInserted > 0) {
                    Voucher::insert($vouchersToInsert);
                }
                
                if ($currentInserted < $targetQuantity) {
                    throw new \Exception("Удалось создать только $currentInserted из $targetQuantity ваучеров из-за коллизий кодов. Пожалуйста, попробуйте еще раз.");
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
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
