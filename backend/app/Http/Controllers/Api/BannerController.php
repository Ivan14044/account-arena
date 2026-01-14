<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Get active banners by position
     */
    public function index(Request $request)
    {
        $position = $request->get('position', 'home_top');
        $cacheKey = "banners_pos_{$position}";
        
        $banners = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($position) {
            return Banner::active()
                ->byPosition($position)
                ->orderBy('order')
                ->get(['id', 'title', 'title_en', 'title_uk', 'image_url', 'link', 'position', 'open_new_tab', 'order']);
        });

        return response()->json($banners);
    }

    /**
     * Get all active banners grouped by position
     */
    public function all()
    {
        $cacheKey = "banners_all";
        
        $banners = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () {
            return Banner::active()
                ->orderBy('position')
                ->orderBy('order')
                ->get(['id', 'title', 'title_en', 'title_uk', 'image_url', 'link', 'position', 'open_new_tab', 'order'])
                ->groupBy('position');
        });

        return response()->json($banners);
    }
}
