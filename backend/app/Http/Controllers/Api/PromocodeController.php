<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromocodeValidationService;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    public function validateCode(Request $request, PromocodeValidationService $service)
    {
        $userId = optional($request->user())->id;
        $data = $service->validate((string)$request->input('code', ''), $userId);
        $status = ($data['ok'] ?? false) ? 200 : 422;
        return response()->json($data, $status);
    }
}


