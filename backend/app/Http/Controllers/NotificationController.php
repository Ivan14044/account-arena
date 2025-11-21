<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $limit = min((int) $request->input('limit', 10), 100);
        $offset = max((int) $request->input('offset', 0), 0);

        $notifications = $user->notifications()
            ->with(['template', 'template.translations'])
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalCount = $user->notifications()->count();
        $unreadCount = $user->notifications()->whereNull('read_at')->count();

        return ApiResponse::success([
            'total' => $totalCount,
            'unread' => $unreadCount,
            'items' => $notifications->map(function ($notification) {
                // Проверяем наличие template (может быть null)
                if (!$notification->template) {
                    \Log::warning('[NOTIFICATIONS] Notification without template', ['notification_id' => $notification->id]);
                    return null; // Пропускаем уведомления без шаблона
                }

                $translations = [];

                foreach ($notification->template->translations as $translation) {
                    $locale = $translation->locale;
                    $code = $translation->code;
                    $value = $translation->value;

                    $translations[$locale][$code] = $value;
                }

                return [
                    'id' => $notification->id,
                    'template' => [
                        'variables' => $notification->variables,
                        'id' => $notification->template->id,
                        'translations' => $translations,
                    ],
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->toDateTimeString(),
                ];
            })->filter()->values(), // Убираем null значения
        ]);
    }

    public function markNotificationsAsRead(\App\Http\Requests\Notification\MarkAsReadRequest $request)
    {
        // Валидация вынесена в FormRequest

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user->notifications()
            ->whereIn('id', $request->input('ids'))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success();
    }

    public function markAllAsRead(Request $request)
    {
        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success();
    }
}
