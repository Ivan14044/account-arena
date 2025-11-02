<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        // Если уже авторизован
        if (auth()->check()) {
            // Если поставщик - на дашборд
            if (auth()->user()->is_supplier) {
                return redirect()->route('supplier.dashboard');
            }
            // Если не поставщик - выходим и показываем форму
            auth()->logout();
        }

        return view('supplier.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Проверяем, является ли пользователь поставщиком
            if (!$user->is_supplier) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'У вас нет доступа к кабинету поставщика. Обратитесь к администратору.',
                ]);
            }

            return redirect()->intended(route('supplier.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Неверный email или пароль.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('supplier.login');
    }
}
