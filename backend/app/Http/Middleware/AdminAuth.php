<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->is_admin) {
            if (auth()->user()->is_blocked) {
                auth()->logout();
                return redirect(route('admin.login'));
            }

            return $next($request);
        }

        // Если пользователь не админ - разлогинить и перенаправить на вход
        if (auth()->check()) {
            auth()->logout();
        }

        return redirect(route('admin.login'));
    }
}
