<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnsureCanonicalUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Don't redirect for non-GET requests or console
        if (!$request->isMethod('get') || app()->runningInConsole()) {
            return $next($request);
        }

        $host = $request->getHost();
        $scheme = $request->getScheme();
        $shouldRedirect = false;

        // 1. Enforce HTTPS
        if ($scheme !== 'https' && app()->environment('production')) {
            $scheme = 'https';
            $shouldRedirect = true;
        }

        // 2. Remove 'www.'
        if (Str::startsWith($host, 'www.')) {
            $host = substr($host, 4);
            $shouldRedirect = true;
        }

        if ($shouldRedirect) {
            $url = $scheme . '://' . $host . $request->getRequestUri();
            return redirect($url, 301);
        }

        return $next($request);
    }
}
