<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Определяем куда редиректить в зависимости от пути
        if ($request->is('supplier') || $request->is('supplier/*')) {
            return route('supplier.login');
        }

        return route('admin.login');
    }
}
