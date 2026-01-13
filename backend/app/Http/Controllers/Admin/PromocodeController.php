<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromocodeController extends Controller
{
    protected $promocodeService;

    public function __construct(\App\Services\PromocodeService $promocodeService)
    {
        $this->promocodeService = $promocodeService;
    }

    public function index()
    {
        $promocodes = Promocode::orderBy('id', 'desc')->get();

        return view('admin.promocodes.index', compact('promocodes'));
    }

    public function create()
    {
        return view('admin.promocodes.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getRules());
        $validator->after(function ($v) use ($request) {
            // Require prefix for bulk creation
            $quantity = (int) ($request->input('quantity', 1));
            if ($quantity > 1 && !filled($request->input('prefix'))) {
                $v->errors()->add('prefix', 'Префикс обязателен для массового создания.');
            }
        });
        $validated = $validator->validate();

        $quantity = (int)($request->input('quantity', 1));
        
        if ($quantity <= 1) {
            $promocode = Promocode::create([
                'code' => $validated['code'],
                'type' => $validated['type'],
                'prefix' => $validated['prefix'] ?? null,
                'batch_id' => trim((string)$request->input('batch_id', '')) ?: null,
                'percent_discount' => $validated['percent_discount'] ?? 0,
                'usage_limit' => $validated['usage_limit'] ?? 0,
                'per_user_limit' => $validated['per_user_limit'] ?? 1,
                'starts_at' => $validated['starts_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            return redirect()->route('admin.promocodes.index')->with('success', 'Промокод успешно создан.');
        }

        $created = $this->promocodeService->bulkCreate($validated + ['batch_id' => $request->input('batch_id')]);

        return redirect()->route('admin.promocodes.index')->with('success', "Создано {$created} промокодов в партии.");
    }

    public function edit(Promocode $promocode)
    {
        return view('admin.promocodes.edit', compact('promocode'));
    }

    public function update(Request $request, Promocode $promocode)
    {
        $validated = Validator::make($request->all(), $this->getRules($promocode->id))->validate();

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
                'type' => ['required', 'in:discount'],
                'prefix' => ['nullable', 'string', 'max:64'],
                'batch_id' => ['nullable', 'string', 'max:64', 'unique:promocodes,batch_id'],
                'percent_discount' => ['required', 'integer', 'between:0,100'],
                'usage_limit' => ['required', 'integer', 'between:0,100000000'],
                'per_user_limit' => ['required', 'integer', 'between:0,100000000'],
                'is_active' => ['required', 'boolean'],
                'starts_at' => ['nullable', 'date'],
                'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ];
        }

        // Update rules (single record only)
        return [
            'code' => ['required', 'string', 'max:64', $unique],
            'type' => ['required', 'in:discount'],
            'prefix' => ['nullable', 'string', 'max:64'],
            'percent_discount' => ['required', 'integer', 'between:0,100'],
            'usage_limit' => ['required', 'integer', 'between:0,100000000'],
            'per_user_limit' => ['required', 'integer', 'between:0,100000000'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
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
}


