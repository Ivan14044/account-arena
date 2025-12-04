<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Не используем guest middleware, чтобы избежать циклических редиректов
        // Проверка будет в showLoginForm()
        $this->middleware('auth')->only('logout');
    }

    public function showLoginForm()
    {
        // Если уже авторизован как админ - редирект на dashboard
        if (auth()->check() && auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        // Если авторизован как не-админ - разлогинить и показать форму
        if (auth()->check() && !auth()->user()->is_admin) {
            auth()->logout();
        }

        return view('auth.login');
    }

    protected function validateLogin(Request $request)
    {
        $credentials = $request->only($this->username(), 'password');
        $user = \App\Models\User::where('email', $credentials['email'])
            ->where('is_admin', 1)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                $this->username() => ['User not found or not an admin.'],
            ]);
        }

        if ($user && $user->is_blocked) {
            throw ValidationException::withMessages([
                $this->username() => ['Your account has been blocked.'],
            ]);
        }

        $this->validate($request, [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
