<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $currency = Option::get('currency');

        return view('admin.settings.index', compact('currency'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules($request->form));

        foreach ($validated as $key => $value) {
            Option::set($key, $value);
        }

        return redirect()->route('admin.settings.index')
            ->with('active_tab', $request->form)
            ->with('success', 'Settings saved successfully.');
    }

    private function getRules($form)
    {
        return match ($form) {
            'header_menu' => [
                'header_menu' => ['required', 'array'],
            ],
            'footer_menu' => [
                'footer_menu' => ['required', 'array'],
            ],
            'cookie' => [
                'cookie_countries' => ['required', 'array'],
            ],
            'smtp' => [
                'from_address' => ['required', 'email'],
                'from_name' => ['required', 'string'],
                'host' => ['required'],
                'port' => ['required', 'integer'],
                'encryption' => ['required', 'string'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            default => [
                'currency' => ['required', 'string'],
                'trial_days' => ['required', 'integer', 'between:0,30'],
                'discount_2' => ['required', 'integer'],
                'discount_3' => ['required', 'integer'],
            ],
        };
    }
}
