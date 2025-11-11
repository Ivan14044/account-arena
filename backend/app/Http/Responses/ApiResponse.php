<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Унифицированные ответы API.
 * ВНИМАНИЕ: сохраняем совместимость с текущими контрактами.
 */
final class ApiResponse
{
    /**
     * Успешный ответ со статусом 200 (по умолчанию).
     * Объединяет переданные данные с полем success=true без изменения структуры ключей.
     */
    public static function success(array $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $data), $status, $headers);
    }
}


