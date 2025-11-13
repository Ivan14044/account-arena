<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Перенаправление на страницу авторизации Google
     */
    public function redirectToGoogle(Request $request)
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Перенаправление на страницу авторизации Google с принудительным выбором аккаунта
     */
    public function redirectToGoogleWithPrompt()
    {
        Log::info('Google OAuth redirect with prompt', [
            'redirect_uri' => config('services.google.redirect'),
            'client_id' => config('services.google.client_id')
        ]);

        // Используем метод with() для передачи параметра prompt (как рекомендует документация)
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Получение информации о пользователе из Google
     */
    public function handleGoogleCallback()
    {
        try {
            Log::info('Google callback begin');
            $googleUser = Socialite::driver('google')->user();

            Log::info('Google user received', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName()
            ]);

            // Шаг 1: ищем пользователя по email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Шаг 2: обновляем google_id, если он еще не сохранён
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'provider' => 'google',
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                // Шаг 3: создаем нового пользователя
                $user = User::create([
                    'provider' => 'google',
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(rand(100000, 999999)),
                ]);
            }

            // Проверка на блокировку
            if ($user->is_blocked) {
                Log::warning('User blocked', ['id' => $user->id]);
                return view('auth.callback', [
                    'success' => false,
                    'error' => 'Ваш аккаунт заблокирован',
                ]);
            }

            // Авторизация
            Auth::login($user);

            // Токен для API
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->active_services = $user->activeServices();
            $userData = $user->only(['id', 'name', 'email', 'avatar']);

            return view('auth.callback', [
                'success' => true,
                'token' => $token,
                'user' => $userData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in Google callback', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('auth.callback', [
                'success' => false,
                'error' => 'Ошибка авторизации: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle Telegram callback
     */
    public function handleTelegramCallback(Request $request)
    {
        try {
            $telegramData = $request->all();

            if (!$this->validateTelegramData($telegramData)) {
                return response()->json(['error' => 'Invalid Telegram data'], 401);
            }

            $user = User::where('telegram_id', $telegramData['id'])->first();

            if (!$user) {
                $existingUser = null;
                if (!empty($telegramData['email'])) {
                    $existingUser = User::where('email', $telegramData['email'])->first();
                }

                if ($existingUser) {
                    $existingUser->telegram_id = $telegramData['id'];
                    $existingUser->telegram_username = $telegramData['username'] ?? null;
                    $existingUser->provider = 'telegram';
                    $existingUser->save();
                    $user = $existingUser;
                } else {
                    $user = User::create([
                        'provider' => 'telegram',
                        'telegram_id' => $telegramData['id'],
                        'telegram_username' => $telegramData['username'] ?? null,
                        'name' => $telegramData['first_name'] . ' ' . ($telegramData['last_name'] ?? ''),
                        'email' => $telegramData['email'] ?? $telegramData['id'] . '@telegram.org',
                        'avatar' => $telegramData['photo_url'] ?? null,
                        'password' => Hash::make(rand(100000, 999999)),
                    ]);
                }
            } else {
                $user->telegram_username = $telegramData['username'] ?? $user->telegram_username;
                $user->avatar = $telegramData['photo_url'] ?? $user->avatar;
                $user->save();
            }

            if ($user->is_blocked) {
                return response()->json(['error' => 'Account blocked'], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $user->active_services = $user->activeServices();

            return response()->json(['token' => $token, 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check Telegram data
     */
    private function validateTelegramData($data)
    {
        // If required fields are missing, data is invalid
        if (!isset($data['id']) || !isset($data['auth_date']) || !isset($data['hash'])) {
            return false;
        }

        // Check if the authentication is not outdated (not more than 24 hours)
        if (time() - $data['auth_date'] > 86400) {
            return false;
        }

        // Get the secret key for verification
        $botToken = config('telegram.bot_token');
        $secretKey = hash('sha256', $botToken, true);

        // Prepare data for hash verification (copy and remove hash)
        $checkData = $data;
        $checkHash = $checkData['hash'];
        unset($checkData['hash']);
        $dataCheckArray = [];

        foreach ($checkData as $key => $value) {
            $dataCheckArray[] = $key . '=' . $value;
        }
        // Sort array
        sort($dataCheckArray);
        $dataCheckString = implode("\n", $dataCheckArray);

        // Calculate hash and compare with the one received from Telegram
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hash, $checkHash);
    }
}
