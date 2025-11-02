<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('translations')
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->get();

        $data = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'code' => $service->code,
                'logo' => $service->logo,
                'amount' => $service->amount,
                'trial_amount' => $service->trial_amount,
                'params' => $service->params,
                'translations' => $service->translations->groupBy('locale')->map(function ($translations) {
                    return $translations->pluck('value', 'code');
                })
            ];
        });

        return response()->json($data);
    }
}


