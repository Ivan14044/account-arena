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
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://cdn.jsdelivr.net https://cdn.jsdelivr.net http://cdn.datatables.net https://cdn.datatables.net http://cdnjs.cloudflare.com https://cdnjs.cloudflare.com http://code.jquery.com https://code.jquery.com http://cdn.ckeditor.com https://cdn.ckeditor.com https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com",
            "style-src 'self' 'unsafe-inline' http://fonts.googleapis.com https://fonts.googleapis.com http://cdn.jsdelivr.net https://cdn.jsdelivr.net http://cdn.datatables.net https://cdn.datatables.net http://cdnjs.cloudflare.com https://cdnjs.cloudflare.com http://code.jquery.com https://code.jquery.com",
            "font-src 'self' http://fonts.gstatic.com https://fonts.gstatic.com data: http://cdn.jsdelivr.net https://cdn.jsdelivr.net https://r2cdn.perplexity.ai",
            "img-src 'self' data: http: https: blob: https://www.googletagmanager.com https://www.google-analytics.com",
            "connect-src 'self' {$appUrl} {$frontendUrl} https://api.monobank.ua https://api.cryptomus.com http://cdn.ckeditor.com https://cdn.ckeditor.com http://cdn.datatables.net https://cdn.datatables.net http://cdn.jsdelivr.net https://cdn.jsdelivr.net https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com https://region1.google-analytics.com",
            "frame-src 'self' https://www.googletagmanager.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}



