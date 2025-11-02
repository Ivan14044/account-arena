<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = array_keys(Config::get('langs', []));

        $locale = $request->query('lang')
            ?? $request->header('X-Locale')
            ?? $this->fromAcceptLanguage($request->header('Accept-Language'))
            ?? config('app.locale');

        $locale = $this->normalize($locale);

        if (!in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function fromAcceptLanguage(?string $header): ?string
    {
        if (!$header) return null;
        $first = explode(',', $header)[0] ?? '';
        $primary = strtolower(trim($first));
        $primary = explode(';', $primary)[0];
        $primary = explode('-', $primary)[0];
        return $primary ?: null;
    }

    private function normalize(?string $locale): ?string
    {
        if (!$locale) return null;
        $l = strtolower($locale);
        if ($l === 'ua') {
            return 'uk';
        }
        if (str_starts_with($l, 'zh')) {
            return 'zh';
        }
        return $l;
    }
}
