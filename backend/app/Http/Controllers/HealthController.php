<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\JsonResponse;

/**
 * Health check контроллер для мониторинга состояния сервисов
 */
class HealthController extends Controller
{
    /**
     * Комплексная проверка здоровья всех сервисов
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = !in_array('down', $checks, true);

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => $checks,
        ], 200);
    }

    /**
     * Быстрая проверка доступности (для load balancer)
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Проверка подключения к БД
     */
    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            DB::connection()->getDatabaseName();
            return 'up';
        } catch (\Exception $e) {
            \Log::error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);
            return 'down';
        }
    }

    /**
     * Проверка подключения к Redis
     */
    private function checkRedis(): string
    {
        try {
            Redis::ping();
            return 'up';
        } catch (\Throwable $e) {
            \Log::error('Redis health check failed', [
                'error' => $e->getMessage(),
            ]);
            return 'down';
        }
    }

    /**
     * Проверка доступности storage
     */
    private function checkStorage(): string
    {
        try {
            $testFile = storage_path('framework/cache/health_check.txt');
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);
            
            return $content === 'test' ? 'up' : 'down';
        } catch (\Exception $e) {
            \Log::error('Storage health check failed', [
                'error' => $e->getMessage(),
            ]);
            return 'down';
        }
    }
}



