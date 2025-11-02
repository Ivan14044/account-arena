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
        $subscriptions = $user->subscriptions()
            ->orderByDesc('created_at')
            ->get();

        return view('admin.users.edit', compact('user', 'subscriptions'));
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

        $user->update([
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
        ]);

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


    public function subscriptions(User $user)
    {
        $subscriptions = $user->subscriptions()
            ->orderByDesc('created_at')
            ->get();

        return view('admin.subscriptions.index', compact('subscriptions', 'user'));
    }
}
