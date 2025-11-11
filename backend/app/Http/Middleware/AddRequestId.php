<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для добавления уникального Request ID к каждому запросу
 * Используется для трейсинга и корреляции логов
 */
class AddRequestId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Генерируем или используем существующий Request ID
        $requestId = $request->header('X-Request-ID') ?: Str::uuid()->toString();
        
        // Сохраняем в request для доступа в приложении
        $request->headers->set('X-Request-ID', $requestId);
        
        // Добавляем в response headers
        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);
        
        return $response;
    }
}



