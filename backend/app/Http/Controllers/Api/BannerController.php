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
        
        $banners = Banner::active()
            ->byPosition($position)
            ->orderBy('order')
            ->get(['id', 'title', 'title_en', 'title_uk', 'image_url', 'link', 'position', 'open_new_tab', 'order']);

        return response()->json($banners);
    }

    /**
     * Get all active banners grouped by position
     */
    public function all()
    {
        $banners = Banner::active()
            ->orderBy('position')
            ->orderBy('order')
            ->get(['id', 'title', 'title_en', 'title_uk', 'image_url', 'link', 'position', 'open_new_tab', 'order'])
            ->groupBy('position');

        return response()->json($banners);
    }
}
