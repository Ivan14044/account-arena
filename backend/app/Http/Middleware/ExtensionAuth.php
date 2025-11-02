<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;

class ExtensionAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 0) токен из cookie
        $raw = (string) $request->cookie('sc_auth', '');
        if ($raw === '') {
            Log::warning('ExtensionAuth:no_cookie', ['have' => array_keys($request->cookies->all())]);
            return response()->json(['message' => 'Unauthorized (no cookie)'], 401);
        }

        // санитизация
        $token = urldecode(trim($raw));
        $token = trim($token, "\"' \t\n\r\0\x0B");

        // fallback: если вдруг передаём токен заголовком (на время диагностики)
        $headerToken = $request->header('X-EXT-TOKEN');
        if (!$token && $headerToken) $token = $headerToken;

        // быстрая диагностика формата
        $looksLikePat = str_contains($token, '|');
        $looksLikeJwt = (bool)preg_match('/^[A-Za-z0-9\-\_=]+\.[A-Za-z0-9\-\_=]+\.([A-Za-z0-9\-\_=]+)$/', $token);
        Log::debug('ExtensionAuth:format', ['pat' => $looksLikePat, 'jwt' => $looksLikeJwt, 'len' => strlen($token)]);

        $user = null;

        // 1) Sanctum PAT (id|plaintext)
        if ($looksLikePat && class_exists(PersonalAccessToken::class)) {
            try {
                $pat = PersonalAccessToken::findToken($token);
                
                if ($pat) {
                    if (!$pat->can('extension')) {
                        return response()->json(['message' => 'Forbidden'], 403);
                    }

                    // при желании: if (!$pat->can('extension')) return response()->json(['message'=>'Forbidden'], 403);
                    $user = $pat->tokenable;
                    Log::info('ExtensionAuth:ok_pat', ['user_id' => $user?->id, 'pat_id' => $pat->id]);
                } else {
                    Log::warning('ExtensionAuth:pat_not_found');
                }
            } catch (\Throwable $e) {
                Log::warning('ExtensionAuth:pat_error', ['err' => $e->getMessage()]);
            }
        }

        // 2) (опционально) JWT — только если реально кладёте JWT в sc_auth
        // if (!$user && $looksLikeJwt) { ... decode -> $user = User::find($sub) ... }

        // 3) session guard (редко)
        if (!$user && auth()->check()) {
            $user = auth()->user();
            Log::info('ExtensionAuth:ok_session', ['user_id' => $user->id]);
        }

        if (!$user) {
            Log::warning('ExtensionAuth:unauth', ['sample' => substr($token, 0, 16)]);
            return response()->json(['message' => 'Unauthorized (bad token)'], 401);
        }

        auth()->setUser($user);
        return $next($request);
    }
}
