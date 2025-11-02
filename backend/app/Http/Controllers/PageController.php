<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('translations')
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');

        $data = $pages->map(function ($service) {
            return $service->translations->groupBy('locale')->map(function ($translations) {
                return $translations->pluck('value', 'code');
            });
        });

        return response()->json($data);
    }
}
