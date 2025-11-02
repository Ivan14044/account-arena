<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
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
        $domain = config('session.domain', env('APP_COOKIE_DOMAIN', '.subcloudy.com'));

        return cookie(
            'sc_auth',
            $token,
            $minutes,
            '/',
            $domain,
            true,   // secure
            true,   // httpOnly
            false,  // raw
            'none'  // SameSite=None
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

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

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        EmailService::configureMailFromOptions();

        \Illuminate\Support\Facades\RateLimiter::clear("password.reset:" . $request->ip());

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['errors' => ['message' => [__($status)]]], 400);
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',
                'lang' => ['required', 'in:en,uk,ru'],
            ]);

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

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'remember' => 'boolean',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => [__('auth.invalid_credentials')],
                ]);
            }

            if ($user->is_blocked) {
                throw ValidationException::withMessages([
                    'email' => [__('auth.account_blocked')],
                ]);
            }

            if ($request->boolean('remember')) {
                Config::set('sanctum.expiration', 43200);
            }

            if ($user->is_pending) {
                if (!empty($user->sub_data['services']) && !empty($user->sub_data['days'])) {
                    $services = array_map('intval', $user->sub_data['services']);
                    foreach ($services as $serviceId) {
                        Subscription::create([
                            'user_id' => $user->id,
                            'status' => Subscription::STATUS_ACTIVE,
                            'payment_method' => 'default',
                            'service_id' => $serviceId,
                            'next_payment_at' => Carbon::now()->addDays($user->sub_data['days']),
                        ]);
                    }
                }
                $user->is_pending = 0;
                $user->save();
            }

            // токен для фронта (если используешь)
            $spaToken = $user->createToken('auth_token')->plainTextToken;
            // токен для расширения со скоупом "extension" -> в cookie
            $extToken = $user->createToken('extension', ['extension'])->plainTextToken;

            $user->load(['subscriptions' => fn($q) => $q->orderBy('id', 'desc')]);
            $user->active_services = $user->activeServices();

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
                'lang' => ['sometimes', 'in:en,uk,ru,zh,es'],
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

        $user->load(['subscriptions' => fn($q) => $q->orderBy('id', 'desc')]);
        $user->active_services = $user->activeServices();

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => __('auth.logged_out')])
            ->withCookie($this->buildAuthCookie('', -60));
    }
}
