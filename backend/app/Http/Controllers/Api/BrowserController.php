<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        if ($request->has('profile')) {
            $profile = $request->profile;
        } else {
            // Get any available account if needed
            $account = ServiceAccount::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expiring_at')->orWhere('expiring_at', '>', now());
                })
                ->orderBy('id', 'asc')
                ->first();
            $profile = $account->profile_id ?? null;
        }

        if ($appUrl && !Str::startsWith($appUrl, ['http://', 'https://'])) {
            $appUrl = 'https://' . ltrim($appUrl, '/');
        }

        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $appUrl = 'https://google.com';
        }

        $user = $this->getApiUser($request);
        if ($user) {
            $appUrl .= '#sc_pair=sc_u_' . $user->id;
        }

        $resp = Http::timeout(60)->get($base . '/new', [
            'app' => $appUrl,
            'profile' => $profile,
            'lang' => $request->uiLanguage ?? 'en',
        ]);

        return response($resp->body(), $resp->status())
            ->withHeaders(['Content-Type' => 'application/json']);
    }
}
