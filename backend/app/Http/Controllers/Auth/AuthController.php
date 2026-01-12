<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationTemplateService;
use App\Services\EmailService;
use App\Services\NotifierService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function buildAuthCookie(string $token, int $minutes = 60 * 24 * 7)
    {
        // Для локальной разработки используем null в качестве домена
        $isLocal = app()->environment('local') || request()->getHost() === 'localhost' || str_starts_with(request()->getHost(), '127.0.0.1');
        $domain = $isLocal ? null : config('session.domain', env('APP_COOKIE_DOMAIN', '.account-arena.com'));

        return cookie(
            'sc_auth',
            $token,
            $minutes,
            '/',
            $domain,
            $isLocal ? false : true,   // secure: false для localhost
            true,   // httpOnly
            false,  // raw
            $isLocal ? 'lax' : 'none'  // SameSite: lax для localhost
        );
    }

    public function resetPassword(\App\Http\Requests\Auth\ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['errors' => ['message' => [__($status)]]], 400);
    }

    public function forgotPassword(\App\Http\Requests\Auth\ForgotPasswordRequest $request)
    {
        EmailService::configureMailFromOptions();

        \Illuminate\Support\Facades\RateLimiter::clear("password.reset:" . $request->ip());

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['errors' => ['message' => [__($status)]]], 400);
    }

    public function register(\App\Http\Requests\Auth\RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'lang' => $validated['lang'],
                'password' => Hash::make($validated['password']),
            ]);

            app(NotificationTemplateService::class)->sendToUser($user, 'registration');

            $user->active_services = [];
            $user->subscriptions = [];

            // токен для фронта (если используешь)
            $spaToken = $user->createToken('auth_token')->plainTextToken;
            // токен для расширения со скоупом "extension" -> в cookie
            $extToken = $user->createToken('extension', ['extension'])->plainTextToken;

            NotifierService::send(
                'registration',
                __('notifier.new_user_title'),
                __('notifier.new_user_message', [
                    'email' => $user->email,
                    'name' => $user->name,
                ])
            );

            return response()->json([
                'message' => __('auth.user_registered'),
                'token' => $spaToken,
                'user' => $user,
            ])->withCookie($this->buildAuthCookie($extToken));
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function login(\App\Http\Requests\Auth\LoginRequest $request)
    {
        try {
            \Log::info('[AUTH] Login request received', ['email' => $request->email]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                \Log::warning('[AUTH] Invalid credentials', ['email' => $request->email]);
                throw ValidationException::withMessages([
                    'email' => [__('auth.invalid_credentials')],
                ]);
            }

            if ($user->is_blocked) {
                \Log::warning('[AUTH] Blocked user attempt', ['email' => $request->email]);
                throw ValidationException::withMessages([
                    'email' => [__('auth.account_blocked')],
                ]);
            }
            
            \Log::info('[AUTH] User found and validated', ['user_id' => $user->id, 'email' => $user->email]);

            if ($request->boolean('remember')) {
                Config::set('sanctum.expiration', 43200);
            }

            // Сбрасываем флаг is_pending если был установлен
            if ($user->is_pending) {
                $user->is_pending = 0;
                $user->save();
            }

            // Токен для фронта (SPA)
            $spaToken = $user->createToken('auth_token')->plainTextToken;
            // Токен для расширения со скоупом "extension" -> в cookie
            $extToken = $user->createToken('extension', ['extension'])->plainTextToken;

            // ИСПРАВЛЕНО: Убрана загрузка subscriptions (модель удалена из проекта)
            // $user->load(['subscriptions' => fn($q) => $q->orderBy('id', 'desc')]);
            
            // ИСПРАВЛЕНО: active_services теперь пустой массив (подписки удалены)
            $user->active_services = [];

            \Log::info('[AUTH] Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'has_token' => !empty($spaToken),
                'balance' => $user->balance
            ]);

            return response()->json([
                'token' => $spaToken,
                'user' => $user,
            ])->withCookie($this->buildAuthCookie($extToken));
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = $this->getApiUser($request);
            if (!$user) {
                return response()->json(['message' => 'Invalid token'], 401);
            }

            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string'],
                'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['sometimes', 'nullable', 'string', 'confirmed'],
                'lang' => ['sometimes', 'in:en,uk,ru'], // Поддерживаемые языки: английский, украинский, русский
                'browser_session_pid' => ['sometimes', 'nullable', 'integer'],
                'keyboardLanguages' => ['sometimes', 'array'],
                'keyboardLanguages.*' => ['string'],
            ]);

            if (array_key_exists('name', $validated)) {
                $user->name = $validated['name'];
            }

            if (array_key_exists('email', $validated)) {
                $user->email = $validated['email'];
            }

            if (array_key_exists('password', $validated)) {
                $user->password = $validated['password']
                    ? Hash::make($validated['password'])
                    : $user->password;
            }

            if (array_key_exists('lang', $validated)) {
                $user->lang = $validated['lang'];
            }

            if (array_key_exists('browser_session_pid', $validated)) {
                $user->session_pid = $validated['browser_session_pid'] ?: null;
            }

            if (array_key_exists('keyboardLanguages', $validated)) {
                $currentSettings = $user->extension_settings ?? [];
                $currentSettings['keyboardLanguages'] = array_values(array_unique($validated['keyboardLanguages']));
                // Do not override uiLanguage here
                $user->extension_settings = $currentSettings;
            }

            $user->save();

            return response()->json(['user' => $user]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function user(Request $request)
    {
        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => __('auth.invalid_token')], 401);
        }

        // ИСПРАВЛЕНО: Убрана загрузка subscriptions (модель удалена из проекта)
        // $user->load(['subscriptions' => fn($q) => $q->orderBy('id', 'desc')]);
        
        // ИСПРАВЛЕНО: active_services теперь пустой массив (подписки удалены)
        $user->active_services = [];

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => __('auth.logged_out')])
            ->withCookie($this->buildAuthCookie('', -60));
    }
}
