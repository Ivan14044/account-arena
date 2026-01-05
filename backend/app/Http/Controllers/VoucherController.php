<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    /**
     * Активация ваучера пользователем
     */
    public function activate(\App\Http\Requests\Voucher\ActivateVoucherRequest $request)
    {
        // Валидация вынесена в FormRequest

        $code = strtoupper(trim($request->input('code')));
        $user = $this->getApiUser($request);

        if (!$user) {
            return response()->json(['message' => 'Неавторизован'], 401);
        }

        // ВАЖНО: Используем транзакцию с блокировкой для предотвращения race condition
        return DB::transaction(function () use ($code, $user) {
            // Блокируем ваучер для предотвращения одновременной активации
            $voucher = Voucher::where('code', $code)->lockForUpdate()->first();

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

            // Проверяем, не использован ли уже (повторная проверка после блокировки)
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

            // ВАЖНО: Используем BalanceService для пополнения баланса
            // Это обеспечивает создание BalanceTransaction и синхронизацию с Transaction
            $balanceService = app(\App\Services\BalanceService::class);
            $oldBalance = $user->balance ?? 0;
            
            try {
                $balanceTransaction = $balanceService->topUp(
                    $user,
                    $voucher->amount,
                    \App\Services\BalanceService::TYPE_TOPUP_VOUCHER,
                    [
                        'voucher_id' => $voucher->id,
                        'voucher_code' => $voucher->code,
                    ]
                );

                // Логируем активацию
                \Log::info('Voucher activated', [
                    'voucher_id' => $voucher->id,
                    'voucher_code' => $voucher->code,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'amount' => $voucher->amount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $user->fresh()->balance,
                    'balance_transaction_id' => $balanceTransaction->id ?? null,
                ]);

                return \App\Http\Responses\ApiResponse::success([
                    'message' => "Ваучер успешно активирован! Баланс пополнен на {$voucher->amount} {$voucher->currency}",
                    'voucher' => [
                        'code' => $voucher->code,
                        'amount' => $voucher->amount,
                        'currency' => $voucher->currency,
                    ],
                    'balance' => [
                        'old' => $oldBalance,
                        'new' => $user->fresh()->balance,
                        'added' => $voucher->amount,
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::error('Voucher activation failed', [
                    'voucher_id' => $voucher->id,
                    'voucher_code' => $voucher->code,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                throw ValidationException::withMessages([
                    'code' => ['Ошибка при активации ваучера. Попробуйте позже.'],
                ]);
            }
        });
    }
}



