<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
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

        User::create([
            'name' => explode('@', $request->email)[0], // Use email prefix as name
            'email' => $request->email,
            'is_blocked' => 0,
            'password' => Hash::make($request->password),
            'is_pending' => 0,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно создан.');
    }

    public function edit(User $user)
    {
        // ИСПРАВЛЕНО: Загружаем покупки товаров вместо подписок
        $purchases = $user->purchases()
            ->with(['serviceAccount', 'transaction'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.users.edit', compact('user', 'purchases'));
    }

public function update(Request $request, User $user)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'is_blocked' => 'required',
        'password' => 'nullable|min:6|confirmed',
        'personal_discount' => 'nullable|integer|min:0|max:100',
        'personal_discount_expires_at' => 'nullable|date',
        'is_supplier' => 'nullable|boolean',
        'supplier_balance' => 'nullable|numeric|min:0',
        'supplier_commission' => 'nullable|numeric|min:0|max:100',
        'supplier_hold_hours' => 'nullable|integer|min:0|max:8760', // <-- новое правило
    ]);

    $is_blocked = $request->is_blocked;
    $is_pending = $user->is_pending;
    if ($request->is_blocked == 2) {
        $is_blocked = 0;
        $is_pending = 1;
    } elseif ($request->is_blocked == 0) {
        $is_blocked = 0;
        $is_pending = 0;
    }

    // Подготовим значения для update
    $updateData = [
        'name' => $request->name,
        'email' => $request->email,
        'is_blocked' => $is_blocked,
        'is_pending' => $is_pending,
        'password' => $request->password ? Hash::make($request->password) : $user->password,
        'personal_discount' => $request->personal_discount ?? 0,
        'personal_discount_expires_at' => $request->personal_discount_expires_at,
        'is_supplier' => $request->boolean('is_supplier', false),
        'supplier_balance' => $request->input('supplier_balance', 0),
        'supplier_commission' => $request->input('supplier_commission', 10),
    ];

    // Сохраняем supplier_hold_hours корректно:
    if ($request->filled('supplier_hold_hours')) {
        $updateData['supplier_hold_hours'] = (int) $request->input('supplier_hold_hours');
    } else {
        // если поле не передано (например, форма не отправила), оставляем текущее значение в БД,
        // но если в БД оно было null — ставим дефолт 6
        $updateData['supplier_hold_hours'] = $user->supplier_hold_hours ?? 6;
    }

    $user->update($updateData);

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
        $newBalance = $oldBalance;

        // Выполняем операцию
        switch ($operation) {
            case 'add':
                // Пополнение
                $newBalance = $oldBalance + $amount;
                $operationText = 'пополнен на';
                $paymentMethod = 'admin_balance_topup';
                break;

            case 'subtract':
                // Списание
                $newBalance = $oldBalance - $amount;
                if ($newBalance < 0) {
                    return redirect()
                        ->route('admin.users.edit', $user)
                        ->with('error', "Недостаточно средств. Текущий баланс: $oldBalance USD, попытка списать: $amount USD");
                }
                $operationText = 'уменьшен на';
                $paymentMethod = 'admin_balance_deduction';
                break;

            case 'set':
                // Установка нового баланса
                // ВАЖНО: Запрещаем установку отрицательного баланса
                if ($amount < 0) {
                    return redirect()
                        ->route('admin.users.edit', $user)
                        ->with('error', "Баланс не может быть отрицательным. Минимальное значение: 0 USD");
                }
                $newBalance = $amount;
                $operationText = 'установлен в';
                $paymentMethod = 'admin_balance_adjustment';
                break;
        }

        // Обновляем баланс
        $user->balance = $newBalance;
        $user->save();

        // Создаем запись транзакции для истории
        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'amount' => ($operation === 'subtract') ? -$amount : $amount,
            'currency' => 'USD',
            'payment_method' => $paymentMethod,
            'status' => 'completed',
        ]);

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

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', "Баланс успешно {$operationText} {$amount} USD. Новый баланс: {$newBalance} USD");
    }
}
