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

        $statistics = [
            'total' => $usages->count(),
            'unique_users' => $usages->unique('user_id')->count(),
            'most_used' => $usages->groupBy('promocode_id')->sortByDesc(fn($g) => $g->count())->first()?->first()?->promocode?->code ?? '—',
        ];

        return view('admin.promocodes.usages', compact('usages', 'statistics'));
    }
}
