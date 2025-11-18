<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationSetting;
use Illuminate\Http\Request;

class AdminNotificationSettingsController extends Controller
{
    /**
     * Показать страницу настроек уведомлений
     */
    public function index()
    {
        $settings = AdminNotificationSetting::getOrCreateForUser(auth()->id());

        return view('admin.notification-settings.index', compact('settings'));
    }

    /**
     * Обновить настройки уведомлений
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'registration_enabled' => 'boolean',
            'product_purchase_enabled' => 'boolean',
            'dispute_created_enabled' => 'boolean',
            'payment_enabled' => 'boolean',
            'topup_enabled' => 'boolean',
            'sound_enabled' => 'boolean',
        ]);

        $settings = AdminNotificationSetting::getOrCreateForUser(auth()->id());
        $settings->update($validated);

        return redirect()->route('admin.notification-settings.index')
            ->with('success', 'Настройки уведомлений успешно обновлены.');
    }
}
