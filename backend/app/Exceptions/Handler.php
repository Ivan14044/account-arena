<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => 'Resource not found',
                ], 404);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Not Found',
                ], 404);
            }

            // Ошибки валидации → 422 со стандартным `errors` (фронт читает error.response.data.errors)
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], $exception->status);
            }

            // Аутентификация → 401, авторизация → 403 (иначе маскировались под 500)
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Unauthenticated.',
                ], 401);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'This action is unauthorized.',
                ], 403);
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
        }

        return parent::render($request, $exception);
    }
}
