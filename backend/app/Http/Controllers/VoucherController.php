<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    /**
     * Активация ваучера пользователем
     */
    public function activate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $user = $this->getApiUser($request);

        if (!$user) {
            return response()->json(['message' => 'Неавторизован'], 401);
        }

        // Находим ваучер
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            throw ValidationException::withMessages([
                'code' => ['Ваучер с таким кодом не найден'],
            ]);
        }

        // Проверяем, активен ли ваучер
        if (!$voucher->is_active) {
            throw ValidationException::withMessages([
                'code' => ['Ваучер деактивирован администратором'],
            ]);
        }

        // Проверяем, не использован ли уже
        if ($voucher->isUsed()) {
            throw ValidationException::withMessages([
                'code' => ['Ваучер уже был использован'],
            ]);
        }

        // Проверяем срок действия (если задан)
        if ($voucher->expires_at && $voucher->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['Срок действия ваучера истек'],
            ]);
        }

        // Активируем ваучер
        $voucher->user_id = $user->id;
        $voucher->used_at = now();
        $voucher->save();

        // Пополняем баланс пользователя
        $oldBalance = $user->balance ?? 0;
        $user->balance = $oldBalance + $voucher->amount;
        $user->save();

        // Создаем транзакцию
        Transaction::create([
            'user_id' => $user->id,
            'amount' => $voucher->amount,
            'currency' => $voucher->currency,
            'payment_method' => 'voucher',
            'status' => 'completed',
        ]);

        // Логируем активацию
        \Log::info('Voucher activated', [
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'amount' => $voucher->amount,
            'old_balance' => $oldBalance,
            'new_balance' => $user->balance,
        ]);

        return response()->json([
            'message' => "Ваучер успешно активирован! Баланс пополнен на {$voucher->amount} {$voucher->currency}",
            'voucher' => [
                'code' => $voucher->code,
                'amount' => $voucher->amount,
                'currency' => $voucher->currency,
            ],
            'balance' => [
                'old' => $oldBalance,
                'new' => $user->balance,
                'added' => $voucher->amount,
            ],
        ]);
    }
}

