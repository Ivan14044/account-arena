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

    public function create()
    {
        return view('admin.notification-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules(true));

        $notificationTemplate = NotificationTemplate::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_mass' => $request->input('is_mass', 0),
        ]);

        $notificationTemplate->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.notification-templates.edit', $notificationTemplate->id)
            : route('admin.notification-templates.index');

        return redirect($route)->with('success', 'Шаблон уведомления успешно создан.');
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

        return redirect($route)->with('success', 'Шаблон уведомления успешно обновлен.');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        if (!$notificationTemplate->is_mass) {
            abort(404);
        }

        $notificationTemplate->delete();

        return redirect()->route('admin.notification-templates.index', ['type' => 'custom'])
            ->with('success', 'Шаблон уведомления успешно удален.');
    }

    private function getRules($isCreate = false)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        if ($isCreate) {
            $rules['code'] = 'required|string|max:255|unique:notification_templates,code';
        }

        foreach (config('langs') as $lang => $flag) {
            foreach(NotificationTemplate::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
