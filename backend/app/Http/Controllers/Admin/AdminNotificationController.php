<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;

class AdminNotificationController extends Controller
{
    public function get()
    {
        $notifications = AdminNotification::latest()->take(5)->get();

        $dropdownArray = $notifications->map(function ($n) {
            $diffInSeconds = now()->diffInSeconds($n->created_at);

            if ($diffInSeconds < 60) {
                $time = $diffInSeconds . 's';
            } elseif ($diffInSeconds < 3600) {
                $time = floor($diffInSeconds / 60) . 'm';
            } elseif ($diffInSeconds < 86400) {
                $time = floor($diffInSeconds / 3600) . 'h';
            } else {
                $time = floor($diffInSeconds / 86400) . 'd';
            }

            return [
                'id' => $n->id,
                'icon' => 'fas fa-info-circle',
                'text' => $n->title,
                'time' => $time,
                'url' => route('admin.admin_notifications.read', $n->id),
                'read' => (bool)$n->read,
            ];
        });

        $dropdownHtml = '';

        foreach ($dropdownArray as $key => $notification) {
            $itemClass = $notification['read']
                ? 'dropdown-item'
                : 'dropdown-item bg-light-primary fw-bold';

            $dropdownHtml .= "
                <a href='{$notification['url']}' class='{$itemClass}'>
                    <div class='d-flex justify-content-between align-items-center w-100'>
                        <div><i class='{$notification['icon']} mr-2'></i> {$notification['text']}</div>
                        <span class='text-muted text-xs'>{$notification['time']}</span>
                    </div>
                </a>
            ";

            if ($key < count($notifications) - 1) {
                $dropdownHtml .= "<div class='dropdown-divider'></div>";
            }
        }

        return response()->json([
            'label' => $notifications->where('read', false)->count(),
            'label_color' => 'primary',
            'dropdown' => $dropdownHtml,
        ]);
    }

    public function index()
    {
        $notifications = AdminNotification::orderBy('id', 'desc')->get();

        return view('admin.admin_notifications.index', compact('notifications'));
    }

    public function read($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->markAsRead();

        return redirect()->route('admin.admin_notifications.index');
    }

    public function readAll()
    {
        AdminNotification::where('read', false)->update(['read' => true]);

        return redirect()
            ->route('admin.admin_notifications.index')
            ->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->delete();

        return redirect()
            ->route('admin.admin_notifications.index')
            ->with('success', 'Notification successfully deleted.');
    }
}
