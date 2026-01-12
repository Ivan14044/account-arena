<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['template', 'user'])
            ->orderBy('id', 'desc')
            ->get();

        $statistics = [
            'total' => $notifications->count(),
            'read' => $notifications->whereNotNull('read_at')->count(),
            'unread' => $notifications->whereNull('read_at')->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'statistics'));
    }

    public function create()
    {
        // Services are no longer supported
        $services = collect();

        return view('admin.notifications.create', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['title', 'content'])
        );

        $notificationTemplate = NotificationTemplate::create([
            'code' => 'mass_' . Str::lower(Str::random(8)),
            'name' => 'Mass Notification from ' . date('Y-m-d H:i'),
            'is_mass' => 1,
        ]);

        $notificationTemplate->saveTranslation($validated);

        $users = $this->getTargetUsers(
            $request->input('target'),
            $request->filled('service_id') ? (int) $request->input('service_id') : null
        );

        foreach ($users as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'notification_template_id' => $notificationTemplate->id
            ]);
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Уведомления успешно созданы.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')->with('success', 'Уведомление успешно удалено.');
    }

    private function getRules($id = false)
    {
        $rules = [
            'target' => 'required',
        ];

        foreach (config('langs') as $lang => $flag) {
            $rules['title.' . $lang] = ['required', 'string'];
            $rules['message.' . $lang] = ['required', 'string'];
        }

        return $rules;
    }

    protected function getTargetUsers(string $filter, ?int $serviceId = null)
    {
        // Для маркетплейса цифровых товаров отправляем уведомления всем пользователям
        return match ($filter) {
            default => User::all(),
        };
    }
}
