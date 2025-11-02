<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
        ]);

        $user = $request->user();

        $user->extension_settings = $data['settings'];
        $user->save();

        return response()->json(['ok' => true]);
    }

    public function authStatus(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['authorized' => false]);
        }

        $user->load(['subscriptions' => function($query) {
            $query->orderBy('id', 'desc');
        }]);

        $user->active_services = $user->activeServices();

        return response()->json([
            'authorized' => true,
            'user' => $user,
        ]);
    }
}


