<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromocodeController extends Controller
{
    public function index()
    {
        $promocodes = Promocode::orderBy('id', 'desc')->get();

        return view('admin.promocodes.index', compact('promocodes'));
    }

    public function create()
    {
        $services = Service::withRussianName()->orderBy('position')->get();
        return view('admin.promocodes.create', compact('services'));
    }

    public function store(Request $request)
    {
        $messages = [
            'services.*.free_days.integer' => 'Бесплатные дни должны быть целым числом.',
            'services.*.free_days.min' => 'Бесплатные дни не могут быть меньше 0.',
            'services.*.free_days.max' => 'Бесплатные дни не могут превышать 3650.',
        ];

        $attributes = [];
        foreach ((array) $request->input('services', []) as $serviceId => $serviceData) {
            $id = isset($serviceData['id']) ? $serviceData['id'] : (is_numeric($serviceId) ? $serviceId : null);
            if ($id) {
                $service = Service::find($id);
                if ($service) {
                    $name = $service->getTranslation('name', 'ru') ?? ($service->admin_name ?? ('Сервис #' . $service->id));
                    $attributes["services.$id.free_days"] = 'Бесплатные дни для "' . $name . '"';
                }
            }
        }

        $validator = Validator::make($request->all(), $this->getRules(), $messages, $attributes);
        $validator->after(function ($v) use ($request) {
            // Require prefix for bulk creation
            $quantity = (int) ($request->input('quantity', 1));
            if ($quantity > 1 && !filled($request->input('prefix'))) {
                $v->errors()->add('prefix', 'Префикс обязателен для массового создания.');
            }

            $servicesInput = $request->input('services', []);
            // If type is free_access, require at least one selected service
            if ($request->input('type') === 'free_access') {
                $selected = collect($servicesInput)->filter(function ($s) { return !empty($s['selected']); });
                if ($selected->isEmpty()) {
                    $v->errors()->add('services', 'Для типа "Бесплатный доступ" необходимо выбрать хотя бы один сервис.');
                }
            }
            foreach ($servicesInput as $serviceId => $serviceData) {
                $isSelected = !empty($serviceData['selected']);
                if (!$isSelected) {
                    continue;
                }

                $serviceModel = null;
                if (isset($serviceData['id'])) {
                    $serviceModel = Service::find($serviceData['id']);
                } elseif (is_numeric($serviceId)) {
                    $serviceModel = Service::find($serviceId);
                }

                if ($serviceModel && $serviceModel->is_active) {
                    $freeDays = $serviceData['free_days'] ?? null;
                    if ($freeDays === null || $freeDays === '') {
                        $v->errors()->add("services.$serviceId.free_days", 'Бесплатные дни обязательны для выбранного активного сервиса.');
                        continue;
                    }
                    if (!is_numeric($freeDays) || $freeDays < 0 || $freeDays > 3650) {
                        $v->errors()->add("services.$serviceId.free_days", 'Бесплатные дни должны быть от 0 до 3650 для выбранного активного сервиса.');
                    }
                }
            }
        });
        $validated = $validator->validate();

        $quantity = (int)($request->input('quantity', 1));
        $manualBatchId = trim((string)$request->input('batch_id', '')) ?: null;
        $servicesPayload = $validated['services'] ?? [];

        if ($quantity <= 1) {
            $promocode = Promocode::create([
                'code' => $validated['code'],
                'type' => $validated['type'],
                'prefix' => $validated['prefix'] ?? null,
                'batch_id' => $manualBatchId,
                'percent_discount' => $validated['percent_discount'] ?? 0,
                'usage_limit' => $validated['usage_limit'] ?? 0,
                'per_user_limit' => $validated['per_user_limit'] ?? 1,
                'starts_at' => $validated['starts_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            $this->syncServices($promocode, $servicesPayload);

            return redirect()->route('admin.promocodes.index')->with('success', 'Промокод успешно создан.');
        }

        $batchId = $manualBatchId ?: (string) Str::uuid();
        $prefix = (string) ($validated['prefix'] ?? '');
        $created = 0;

        DB::transaction(function () use ($quantity, $prefix, $validated, $servicesPayload, $batchId, &$created) {
            for ($i = 0; $i < $quantity; $i++) {
                do {
                    $code = $prefix . $this->generateCode(8);
                } while (Promocode::where('code', $code)->exists());

                $promo = Promocode::create([
                    'code' => $code,
                    'type' => $validated['type'],
                    'prefix' => $prefix ?: null,
                    'batch_id' => $batchId,
                    'percent_discount' => $validated['percent_discount'] ?? 0,
                    'usage_limit' => $validated['usage_limit'] ?? 0,
                    'per_user_limit' => $validated['per_user_limit'] ?? 1,
                    'starts_at' => $validated['starts_at'] ?? null,
                    'expires_at' => $validated['expires_at'] ?? null,
                    'is_active' => $validated['is_active'],
                ]);

                $this->syncServices($promo, $servicesPayload);
                $created++;
            }
        });

        return redirect()->route('admin.promocodes.index')->with('success', "Создано {$created} промокодов в партии.");
    }

    public function edit(Promocode $promocode)
    {
        $services = Service::withRussianName()->orderBy('position')->get();
        $promocode->load('services');
        return view('admin.promocodes.edit', compact('promocode', 'services'));
    }

    public function update(Request $request, Promocode $promocode)
    {
        $messages = [
            'services.*.free_days.integer' => 'Бесплатные дни должны быть целым числом.',
            'services.*.free_days.min' => 'Бесплатные дни не могут быть меньше 0.',
            'services.*.free_days.max' => 'Бесплатные дни не могут превышать 3650.',
        ];

        $attributes = [];
        foreach ((array) $request->input('services', []) as $serviceId => $serviceData) {
            $id = isset($serviceData['id']) ? $serviceData['id'] : (is_numeric($serviceId) ? $serviceId : null);
            if ($id) {
                $service = Service::find($id);
                if ($service) {
                    $name = $service->getTranslation('name', 'ru') ?? ($service->admin_name ?? ('Сервис #' . $service->id));
                    $attributes["services.$id.free_days"] = 'Бесплатные дни для "' . $name . '"';
                }
            }
        }

        $validator = Validator::make($request->all(), $this->getRules($promocode->id), $messages, $attributes);
        $validator->after(function ($v) use ($request) {
            $servicesInput = $request->input('services', []);
            if ($request->input('type') === 'free_access') {
                $selected = collect($servicesInput)->filter(function ($s) { return !empty($s['selected']); });
                if ($selected->isEmpty()) {
                    $v->errors()->add('services', 'Для типа "Бесплатный доступ" необходимо выбрать хотя бы один сервис.');
                }
            }
            foreach ($servicesInput as $serviceId => $serviceData) {
                $isSelected = !empty($serviceData['selected']);
                if (!$isSelected) {
                    continue;
                }

                $serviceModel = null;
                if (isset($serviceData['id'])) {
                    $serviceModel = Service::find($serviceData['id']);
                } elseif (is_numeric($serviceId)) {
                    $serviceModel = Service::find($serviceId);
                }

                if ($serviceModel && $serviceModel->is_active) {
                    $freeDays = $serviceData['free_days'] ?? null;
                    if ($freeDays === null || $freeDays === '') {
                        $v->errors()->add("services.$serviceId.free_days", 'Бесплатные дни обязательны для выбранного активного сервиса.');
                        continue;
                    }
                    if (!is_numeric($freeDays) || $freeDays < 0 || $freeDays > 3650) {
                        $v->errors()->add("services.$serviceId.free_days", 'Бесплатные дни должны быть от 0 до 3650 для выбранного активного сервиса.');
                    }
                }
            }
        });
        $validated = $validator->validate();

        $promocode->update([
            'code' => $validated['code'] ?? $promocode->code,
            'type' => $validated['type'] ?? $promocode->type,
            'prefix' => $validated['prefix'] ?? $promocode->prefix,
            'percent_discount' => $validated['percent_discount'] ?? 0,
            'usage_limit' => $validated['usage_limit'] ?? 0,
            'per_user_limit' => $validated['per_user_limit'] ?? $promocode->per_user_limit,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $this->syncServices($promocode, $validated['services'] ?? []);

        $route = $request->has('save')
            ? route('admin.promocodes.edit', $promocode->id)
            : route('admin.promocodes.index');

        return redirect($route)->with('success', 'Промокод успешно обновлен.');
    }

    public function destroy(Promocode $promocode)
    {
        $promocode->delete();

        return redirect()->route('admin.promocodes.index')->with('success', 'Промокод успешно удален.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(array_map('intval', (array)$request->input('ids', [])));
        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 422);
        }
        Promocode::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Deleted', 'deleted' => count($ids)]);
    }

    private function getRules($id = false): array
    {
        $unique = $id ? 'unique:promocodes,code,' . $id : 'unique:promocodes,code';

        if ($id === false) {
            // Create rules (support bulk)
            return [
                'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
                'code' => ['required_if:quantity,1', 'nullable', 'string', 'max:64', $unique],
                'type' => ['required', 'in:discount,free_access'],
                'prefix' => ['nullable', 'string', 'max:64'],
                'batch_id' => ['nullable', 'string', 'max:64', 'unique:promocodes,batch_id'],
                'percent_discount' => ['required_if:type,discount', 'nullable', 'integer', 'between:0,100'],
                'usage_limit' => ['required', 'integer', 'between:0,100000000'],
                'per_user_limit' => ['required', 'integer', 'between:0,100000000'],
                'is_active' => ['required', 'boolean'],
                'starts_at' => ['nullable', 'date'],
                'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'services' => ['nullable', 'array'],
                'services.*.id' => ['required_with:services', 'exists:services,id'],
                'services.*.free_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
                'services.*.selected' => ['nullable', 'boolean'],
            ];
        }

        // Update rules (single record only)
        return [
            'code' => ['required', 'string', 'max:64', $unique],
            'type' => ['required', 'in:discount,free_access'],
            'prefix' => ['nullable', 'string', 'max:64'],
            'percent_discount' => ['required_if:type,discount', 'nullable', 'integer', 'between:0,100'],
            'usage_limit' => ['required', 'integer', 'between:0,100000000'],
            'per_user_limit' => ['required', 'integer', 'between:0,100000000'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'services' => ['nullable', 'array'],
            'services.*.id' => ['required_with:services', 'exists:services,id'],
            'services.*.free_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'services.*.selected' => ['nullable', 'boolean'],
        ];
    }

    private function generateCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }

    private function syncServices(Promocode $promocode, array $services): void
    {
        $sync = [];
        foreach ($services as $service) {
            if (!isset($service['id']) || empty($service['selected'])) {
                continue;
            }
            $sync[$service['id']] = ['free_days' => (int)($service['free_days'] ?? 0)];
        }
        $promocode->services()->sync($sync);
    }
}


