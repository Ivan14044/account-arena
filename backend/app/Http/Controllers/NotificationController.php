<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return response()->json([
            'success' => true,
            'total' => $totalCount,
            'unread' => $unreadCount,
            'items' => $notifications->map(function ($notification) {
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
            }),
        ]);
    }

    public function markNotificationsAsRead(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $user = $this->getApiUser($request);
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user->notifications()
            ->whereIn('id', $request->input('ids'))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
