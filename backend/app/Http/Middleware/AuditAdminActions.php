<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для аудита действий администраторов
 * Логирует все POST/PUT/PATCH/DELETE запросы в админ панели
 */
class AuditAdminActions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Логируем только мутирующие операции
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Пропускаем логин/логаут
        if ($request->is('admin/login') || $request->is('admin/logout')) {
            return $response;
        }

        // Если успешный ответ (2xx или 3xx), логируем
        if ($response->isSuccessful() || $response->isRedirection()) {
            $this->logAction($request);
        }

        return $response;
    }

    /**
     * Логирование действия администратора
     */
    private function logAction(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->is_admin) {
            return;
        }

        $action = $this->determineAction($request);
        $modelInfo = $this->extractModelInfo($request);

        AuditLog::log(
            action: $action,
            modelType: $modelInfo['type'] ?? 'Unknown',
            modelId: $modelInfo['id'],
            userId: $user->id,
            changes: $this->extractChanges($request),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );
    }

    /**
     * Определение типа действия
     */
    private function determineAction(Request $request): string
    {
        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown',
        };
    }

    /**
     * Извлечение информации о модели из URL
     */
    private function extractModelInfo(Request $request): array
    {
        $path = $request->path();
        
        // Примеры: admin/users/5, admin/service-accounts/10/export
        if (preg_match('#admin/([^/]+)/(\d+)#', $path, $matches)) {
            return [
                'type' => $this->normalizeModelName($matches[1]),
                'id' => (int) $matches[2],
            ];
        }

        // Для create endpoints: admin/users
        if (preg_match('#admin/([^/]+)$#', $path, $matches)) {
            return [
                'type' => $this->normalizeModelName($matches[1]),
                'id' => null,
            ];
        }

        return ['type' => null, 'id' => null];
    }

    /**
     * Нормализация имени модели
     */
    private function normalizeModelName(string $resourceName): string
    {
        $map = [
            'users' => 'User',
            'admins' => 'User',
            'service-accounts' => 'ServiceAccount',
            'services' => 'Service',
            'purchases' => 'Purchase',
            'promocodes' => 'Promocode',
            'vouchers' => 'Voucher',
            'banners' => 'Banner',
            'articles' => 'Article',
            'pages' => 'Page',
            'categories' => 'Category',
            'product-categories' => 'Category',
        ];

        return $map[$resourceName] ?? ucfirst($resourceName);
    }

    /**
     * Извлечение изменений из request
     */
    private function extractChanges(Request $request): ?array
    {
        $data = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
        ]);

        return !empty($data) ? ['new' => $data] : null;
    }
}



