<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('template')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        $services = Service::all();

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

        return redirect()->route('admin.notifications.index')->with('success', 'Notifications successfully created.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')->with('success', 'Notification successfully deleted.');
    }

    private function getRules($id = false)
    {
        $rules = [
            'target' => 'required',
        ];

        if (in_array(request('target'), ['active_subscribers', 'inactive_subscribers'])) {
            $rules['service_id'] = ['required', 'exists:services,id'];
        }

        foreach (config('langs') as $lang => $flag) {
            $rules['title.' . $lang] = ['required', 'string'];
            $rules['message.' . $lang] = ['required', 'string'];
        }

        return $rules;
    }

    protected function getTargetUsers(string $filter, ?int $serviceId = null)
    {
        return match ($filter) {
            'active_subscribers' => User::whereHas('subscriptions', function ($q) use ($serviceId) {
                $q->where('status', Subscription::STATUS_ACTIVE);
                if ($serviceId) {
                    $q->where('service_id', $serviceId);
                }
            })->get(),

            'inactive_subscribers' => User::whereHas('subscriptions', function ($q) use ($serviceId) {
                $q->where('status', Subscription::STATUS_CANCELED);
                if ($serviceId) {
                    $q->where('service_id', $serviceId);
                }
            })->get(),

            'never_subscribed' => User::whereDoesntHave('subscriptions')->get(),

            default => User::all(),
        };
    }
}
