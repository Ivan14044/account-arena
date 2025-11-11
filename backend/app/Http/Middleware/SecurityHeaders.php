<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Добавление заголовков безопасности для защиты от XSS, clickjacking и других атак
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Защита от clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Защита от MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection для старых браузеров
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Security Policy
        $csp = $this->buildCsp();
        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS для HTTPS (только если в продакшене и HTTPS)
        if (config('app.env') === 'production' && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (ранее Feature-Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }

    /**
     * Построение Content Security Policy
     */
    private function buildCsp(): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $appUrl = config('app.url');

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' {$appUrl} {$frontendUrl} https://api.monobank.ua https://api.cryptomus.com",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}



