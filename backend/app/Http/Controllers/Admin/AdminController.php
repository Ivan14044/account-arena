<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::where('is_admin', true)
            ->where('is_main_admin', false)
            ->orderBy('id', 'desc')
            ->get();

        $statistics = [
            'total' => $users->count(),
            'active' => $users->where('is_blocked', false)->count(),
            'blocked' => $users->where('is_blocked', true)->count(),
        ];

        return view('admin.admins.index', compact('users', 'statistics'));
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_blocked' => $validated['is_blocked'],
            'is_admin' => true,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'Administrator successfully created.');
    }

    public function edit(User $admin)
    {
        // ВАЖНО: Защита Main Admin. Его нельзя редактировать через общий интерфейс управления админами.
        if ($admin->is_main_admin) {
            return redirect()->route('admin.admins.index')->with('error', 'Редактирование главного администратора запрещено.');
        }

        return view('admin.admins.edit', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        if ($admin->is_main_admin) {
            return redirect()->route('admin.admins.index')->with('error', 'Редактирование главного администратора запрещено.');
        }

        $validated = $request->validate($this->getRules($admin->id));

        $admin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_blocked' => $validated['is_blocked'],
            'password' => $validated['password']
                ? Hash::make($validated['password'])
                : $admin->password,
        ]);

        $route = $request->has('save')
            ? route('admin.admins.edit', $admin->id)
            : route('admin.admins.index');

        return redirect($route)->with('success', 'Administrator successfully updated.');
    }

    public function destroy(User $admin)
    {
        if ($admin->is_main_admin) {
            return redirect()->route('admin.admins.index')->with('error', 'Нельзя удалить главного администратора.');
        }

        if ($admin->id === auth()->id()) {
            return redirect()->route('admin.admins.index')->with('error', 'Вы не можете удалить самого себя.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', 'Administrator successfully deleted.');
    }

    public function block(User $admin)
    {
        if ($admin->is_main_admin) {
            return redirect()->route('admin.admins.index')->with('error', 'Нельзя заблокировать главного администратора.');
        }

        $admin->is_blocked = !$admin->is_blocked;
        $admin->save();

        return redirect()->route('admin.admins.index')
            ->with('success', $admin->is_blocked ? 'Administrator has been blocked.' : 'Administrator has been unblocked.');
    }

    private function getRules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'is_blocked' => ['required', 'boolean'],
            'password' => $id
                ? ['nullable', 'min:6', 'confirmed']
                : ['required', 'min:6', 'confirmed'],
        ];
    }
}
