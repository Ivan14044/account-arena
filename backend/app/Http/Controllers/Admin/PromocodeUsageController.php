<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromocodeUsage;

class PromocodeUsageController extends Controller
{
    public function index()
    {
        $usages = PromocodeUsage::with(['promocode', 'user'])
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.promocodes.usages', compact('usages'));
    }
}
