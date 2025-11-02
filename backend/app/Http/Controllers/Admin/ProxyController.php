<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    public function index()
    {
        $proxies = Proxy::orderBy('id', 'desc')->get();

        return view('admin.proxies.index', compact('proxies'));
    }

    public function create()
    {
        return view('admin.proxies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());

        Proxy::create($validated);

        return redirect()->route('admin.proxies.index')->with('success', 'Proxy successfully created.');
    }

    public function edit(Proxy $proxy)
    {
        return view('admin.proxies.edit', compact('proxy'));
    }

    public function update(Request $request, Proxy $proxy)
    {
        $validated = $request->validate($this->getRules());

        $proxy->update($validated);

        $route = $request->has('save')
            ? route('admin.proxies.edit', $proxy->id)
            : route('admin.proxies.index');

        return redirect($route)->with('success', 'Proxy successfully updated.');
    }

    public function destroy(Proxy $proxy)
    {
        $proxy->delete();

        return redirect()->route('admin.proxies.index')->with('success', 'Proxy successfully deleted.');
    }

    private function getRules(): array
    {
        return [
            'type' => 'required|string|in:HTTP,HTTPS,SOCKS4,SOCKS5',
            'address' => 'required|string|max:255',
            'credentials' => 'nullable|string|max:255',
            'country' => 'nullable|string|size:2',
            'is_active' => 'required|boolean',
            'expiring_at' => 'nullable|date',
        ];
    }
}
