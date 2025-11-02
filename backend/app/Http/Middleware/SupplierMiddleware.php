<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем авторизацию
        if (!auth()->check()) {
            // Сохраняем URL для редиректа после входа
            session(['url.intended' => $request->url()]);
            return redirect()->route('supplier.login')->with('error', 'Пожалуйста, войдите в систему.');
        }

        // Проверяем роль поставщика
        if (!auth()->user()->is_supplier) {
            auth()->logout();
            return redirect()->route('supplier.login')->withErrors([
                'email' => 'У вас нет доступа к кабинету поставщика. Обратитесь к администратору.',
            ]);
        }

        return $next($request);
    }
}
