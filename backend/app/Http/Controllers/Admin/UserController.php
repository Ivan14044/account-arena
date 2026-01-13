<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $userService;

    public function __construct(\App\Services\UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = User::where('is_admin', false)
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $this->userService->createUser($validated);

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно создан.');
    }

    public function edit(User $user)
    {
        // Используем сервис для получения истории покупок
        $purchases = $this->userService->getPurchaseHistory($user);

        return view('admin.users.edit', compact('user', 'purchases'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'is_blocked' => 'required',
            'password' => 'nullable|min:6|confirmed',
            'personal_discount' => 'nullable|integer|min:0|max:100',
            'personal_discount_expires_at' => 'nullable|date',
            'is_supplier' => 'nullable|boolean',
            'supplier_balance' => 'nullable|numeric|min:0',
            'supplier_commission' => 'nullable|numeric|min:0|max:100',
            'supplier_hold_hours' => 'nullable|integer|min:1|max:8760',
        ]);

        $this->userService->updateUser($user, $validated);

        $route = $request->has('save')
            ? route('admin.users.edit', $user->id)
            : route('admin.users.index');

        return redirect($route)->with('success', 'Пользователь успешно обновлен.');
    }


    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User successfully deleted.');
    }

    public function block(User $user)
    {
        $user->is_blocked = !$user->is_blocked;
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', $user->is_blocked ? 'User has been blocked.' : 'User has been unblocked.');
    }

    /**
     * Управление балансом пользователя (пополнение/списание/установка)
     * ВАЖНО: Используем BalanceService для обеспечения целостности данных
     */
    public function updateBalance(Request $request, User $user)
    {
        $request->validate([
            'operation' => 'required|in:add,subtract,set',
            'amount' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:500',
        ]);

        $operation = $request->input('operation');
        $amount = $request->input('amount');
        $comment = $request->input('comment', '');
        $oldBalance = $user->balance ?? 0;

        // ВАЖНО: Используем транзакцию для атомарности операций
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($operation, $amount, $user, $oldBalance, $comment) {
                $balanceService = app(\App\Services\BalanceService::class);
                $newBalance = $oldBalance;
                $operationText = '';
                $paymentMethod = '';

                // Выполняем операцию через BalanceService
                switch ($operation) {
                    case 'add':
                        // Пополнение через BalanceService
                        $balanceService->topUp(
                            $user,
                            $amount,
                            \App\Services\BalanceService::TYPE_TOPUP_ADMIN,
                            [
                                'admin_id' => auth()->id(),
                                'admin_email' => auth()->user()->email,
                                'comment' => $comment,
                            ]
                        );
                        $newBalance = $user->fresh()->balance;
                        $operationText = 'пополнен на';
                        $paymentMethod = 'admin_balance_topup';
                        break;

                    case 'subtract':
                        // Списание через BalanceService
                        // BalanceService сам проверит достаточность средств
                        try {
                            $balanceService->deduct(
                                $user,
                                $amount,
                                \App\Services\BalanceService::TYPE_DEDUCTION,
                                [
                                    'admin_id' => auth()->id(),
                                    'admin_email' => auth()->user()->email,
                                    'comment' => $comment,
                                ]
                            );
                            $newBalance = $user->fresh()->balance;
                            $operationText = 'уменьшен на';
                            $paymentMethod = 'admin_balance_deduction';
                        } catch (\Exception $e) {
                            throw new \Exception("Недостаточно средств. Текущий баланс: {$oldBalance} USD, попытка списать: {$amount} USD");
                        }
                        break;

                    case 'set':
                        // Установка нового баланса
                        // ВАЖНО: Запрещаем установку отрицательного баланса
                        if ($amount < 0) {
                            throw new \Exception("Баланс не может быть отрицательным. Минимальное значение: 0 USD");
                        }

                        // Вычисляем разницу и применяем через BalanceService
                        $difference = $amount - $oldBalance;
                        if ($difference > 0) {
                            // Пополняем
                            $balanceService->topUp(
                                $user,
                                $difference,
                                \App\Services\BalanceService::TYPE_ADJUSTMENT,
                                [
                                    'admin_id' => auth()->id(),
                                    'admin_email' => auth()->user()->email,
                                    'comment' => $comment ?: 'Admin balance adjustment (set)',
                                ]
                            );
                        } elseif ($difference < 0) {
                            // Списываем
                            try {
                                $balanceService->deduct(
                                    $user,
                                    abs($difference),
                                    \App\Services\BalanceService::TYPE_ADJUSTMENT,
                                    [
                                        'admin_id' => auth()->id(),
                                        'admin_email' => auth()->user()->email,
                                        'comment' => $comment ?: 'Admin balance adjustment (set)',
                                    ]
                                );
                            } catch (\Exception $e) {
                                throw new \Exception("Недостаточно средств для установки баланса. Текущий баланс: {$oldBalance} USD, требуется: {$amount} USD");
                            }
                        }
                        $newBalance = $user->fresh()->balance;
                        $operationText = 'установлен в';
                        $paymentMethod = 'admin_balance_adjustment';
                        break;
                }

                // Логируем действие администратора
                \Log::info('Admin balance update', [
                    'admin_id' => auth()->id(),
                    'admin_email' => auth()->user()->email,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'operation' => $operation,
                    'amount' => $amount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'comment' => $comment,
                ]);
            });

            // Получаем обновленный баланс для сообщения
            $user->refresh();
            $newBalance = $user->balance ?? 0;
            $operationText = match($operation) {
                'add' => 'пополнен на',
                'subtract' => 'уменьшен на',
                'set' => 'установлен в',
            };

            return redirect()
                ->route('admin.users.edit', $user)
                ->with('success', "Баланс успешно {$operationText} {$amount} USD. Новый баланс: {$newBalance} USD");
        } catch (\Exception $e) {
            \Log::error('Admin balance update failed', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'operation' => $operation,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.users.edit', $user)
                ->with('error', $e->getMessage());
        }
    }
}
