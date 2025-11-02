<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::withEnglishName()->orderBy('id', 'desc')->get();

        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['name', 'description'])
        );

        $path = false;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
        }

        $params = $validated['params'] ?? [];
        if ($request->hasFile('params_icon')) {
            $iconPath = $request->file('params_icon')->store('icons', 'public');
            $params['icon'] = Storage::url($iconPath);
        }

        $service = Service::create([
            'code' => $validated['code'],
            'is_active' => $validated['is_active'],
            'amount' => $validated['amount'],
            'trial_amount' => $validated['trial_amount'],
            'position' => $validated['position'],
            'logo' => $path ? Storage::url($path) : Service::DEFAULT_LOGO,
            'params' => $params,
        ]);

        $service->saveTranslation($validated);

        return redirect()->route('admin.services.index')->with('success', 'Service successfully created.');
    }

    public function edit(Service $service)
    {
        $service->load('translations');
        $serviceData = $service->translations->groupBy('locale')->map(function ($translations) {
            return $translations->pluck('value', 'code')->toArray();
        });

        return view('admin.services.edit', compact('service', 'serviceData'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate(
            $this->getRules($service->id),
            [],
            getTransAttributes(['name', 'description'])
        );

        $path = false;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
        }

        // Merge existing params with incoming to preserve other keys
        $params = array_merge($service->params ?? [], $validated['params'] ?? []);
        // Handle favicon upload/retain/remove
        if ($request->hasFile('params_icon')) {
            $iconPath = $request->file('params_icon')->store('icons', 'public');
            $params['icon'] = Storage::url($iconPath);
        } else {
            // If hidden text exists, keep it; otherwise remove icon key
            if ($request->has('params_icon_text')) {
                $params['icon'] = $request->input('params_icon_text');
            } else {
                unset($params['icon']);
            }
        }

        $service->update([
            'is_active' => $validated['is_active'],
            'amount' => $validated['amount'],
            'trial_amount' => $validated['trial_amount'],
            'position' => $validated['position'],
            'params' => $params,
            'logo' => $path ? Storage::url($path) : ($request->logo_text ?? Service::DEFAULT_LOGO),
        ]);

        $service->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.services.edit', $service->id)
            : route('admin.services.index');

        return redirect($route)->with('success', 'Service successfully updated.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Service successfully deleted.');
    }

    private function getRules($id = false)
    {
        $rules = [
            'code' => $id ? ['nullable'] : ['required', 'unique:services,code'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:10240'],
            'logo_text' => ['nullable', 'string', 'max:255'],
            'params_icon' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg,ico', 'max:10240'],
            'amount' => ['required', 'numeric', 'between:0.1,9999999'],
            'trial_amount' => ['required', 'numeric', 'between:0.1,9999999'],
            'position' => ['required', 'numeric', 'between:0,999999999'],
            'is_active' => ['required', 'boolean'],
            'params.link' => ['required', 'url'],
            'params.title' => ['nullable', 'string', 'max:255'],
        ];

        foreach (config('langs') as $lang => $flag) {
            foreach (Service::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
