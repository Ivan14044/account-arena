<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\SupplierNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $supplier = auth()->user();
        
        $notifications = $supplier->supplierNotifications()
            ->latest()
            ->paginate(20);
        
        return view('supplier.notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = SupplierNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $notification->markAsRead();
        
        return back()->with('success', 'Уведомление отмечено как прочитанное');
    }

    public function markAllAsRead()
    {
        auth()->user()->supplierNotifications()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        
        return back()->with('success', 'Все уведомления отмечены как прочитанные');
    }

    public function getUnreadCount()
    {
        $count = auth()->user()->supplierNotifications()->unread()->count();
        
        return response()->json(['count' => $count]);
    }
}

