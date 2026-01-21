<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function saveSettings(\App\Http\Requests\Extension\SaveSettingsRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        $user->extension_settings = $data['settings'];
        $user->save();

        return \App\Http\Responses\ApiResponse::success(['ok' => true]);
    }

    public function authStatus(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['authorized' => false]);
        }

        // Subscriptions functionality removed
        $user->active_services = [];

        return response()->json([
            'authorized' => true,
            'user' => $user,
        ]);
    }
}


