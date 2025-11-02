<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'system');

        $notificationTemplates = NotificationTemplate::query()
            ->where('is_mass', $type === 'custom' ? 1 : 0)
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.notification-templates.index', compact('notificationTemplates'));
    }

    public function edit(NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->load('translations');
        $notificationTemplateData = $notificationTemplate->translations->groupBy('locale')->map(function ($translations) {
            return $translations->pluck('value', 'code')->toArray();
        });

        return view('admin.notification-templates.edit', compact('notificationTemplate', 'notificationTemplateData'));
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate)
    {
        $validated = $request->validate($this->getRules());

        $notificationTemplate->update($validated);
        $notificationTemplate->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.notification-templates.edit', $notificationTemplate->id)
            : route('admin.notification-templates.index');

        return redirect($route)->with('success', 'Notification template successfully updated.');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        if (!$notificationTemplate->is_mass) {
            abort(404);
        }

        $notificationTemplate->delete();

        return redirect()->route('admin.notification-templates.index', ['type' => 'custom'])
            ->with('success', 'Notification template successfully deleted.');
    }

    private function getRules()
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        foreach (config('langs') as $lang => $flag) {
            foreach(NotificationTemplate::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
