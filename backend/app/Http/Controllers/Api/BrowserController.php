<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BrowserController extends Controller
{
    public function new(Request $request)
    {
        // Services are no longer supported - use default URL
        $base = rtrim(config('services.browser_api.url'), '/');
        $appUrl = $request->app_url ?? 'https://google.com';

        // SECURITY FIX (C3): запускать можно ТОЛЬКО профили из собственных
        // завершённых покупок пользователя. Ранее контроллер доверял любому
        // ?profile= или брал первый активный аккаунт в системе → можно было
        // запустить чужой (неоплаченный) аккаунт в стриминговом браузере.
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        // profile_id'ы аккаунтов, которые пользователь реально купил (completed)
        $ownedProfiles = ServiceAccount::query()
            ->whereNotNull('profile_id')
            ->whereIn(
                'id',
                Purchase::where('user_id', $user->id)
                    ->where('status', Purchase::STATUS_COMPLETED)
                    ->whereNotNull('service_account_id')
                    ->pluck('service_account_id')
            );

        if ($request->filled('profile')) {
            $profile = $request->profile;

            $owns = (clone $ownedProfiles)->where('profile_id', $profile)->exists();
            if (!$owns) {
                abort(403, 'You do not own this profile.');
            }
        } else {
            // Берём профиль из последней завершённой покупки самого пользователя
            $account = $ownedProfiles->orderByDesc('id')->first();
            $profile = $account->profile_id ?? null;

            if (!$profile) {
                abort(404, 'No purchased profile available to launch.');
            }
        }

        if ($appUrl && !Str::startsWith($appUrl, ['http://', 'https://'])) {
            $appUrl = 'https://' . ltrim($appUrl, '/');
        }

        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $appUrl = 'https://google.com';
        }

        // $user уже резолвлен выше через $request->user() (маршрут под auth:sanctum)
        $appUrl .= '#sc_pair=sc_u_' . $user->id;

        $resp = Http::timeout(60)->get($base . '/new', [
            'app' => $appUrl,
            'profile' => $profile,
            'lang' => $request->uiLanguage ?? 'en',
        ]);

        return response($resp->body(), $resp->status())
            ->withHeaders(['Content-Type' => 'application/json']);
    }
}
