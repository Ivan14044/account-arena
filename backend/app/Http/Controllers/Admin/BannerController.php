<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Banner::query();

        // Filter by position
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $banners = $query->orderBy('order')->orderByDesc('created_at')->paginate(20);
        $positions = Banner::getPositions();

        return view('admin.banners.index', compact('banners', 'positions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $positions = Banner::getPositions();
        
        // Get existing banners to show which slots are taken (для home_top)
        $existingBannersHomeTop = Banner::where('position', 'home_top')
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['order', 'title']);
            
        // Get existing wide banner (для home_top_wide)
        $existingWideBanner = Banner::where('position', 'home_top_wide')
            ->where('is_active', true)
            ->first();
        
        return view('admin.banners.create', compact('positions', 'existingBannersHomeTop', 'existingWideBanner'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Определяем максимальное значение order в зависимости от позиции
        $maxOrder = $request->position === 'home_top_wide' ? 1 : 4;
        
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_uk' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB
            'link' => ['nullable', 'url', 'max:255'],
            'position' => ['required', 'string'],
            'order' => ['required', 'integer', 'min:1', "max:$maxOrder"], // Для home_top_wide только 1, для home_top 1-4
            'is_active' => ['required', 'boolean'],
            'open_new_tab' => ['required', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        Banner::create($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Баннер успешно создан!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner)
    {
        $positions = Banner::getPositions();
        
        // Get existing banners (excluding current one) для home_top
        $existingBannersHomeTop = Banner::where('position', 'home_top')
            ->where('is_active', true)
            ->where('id', '!=', $banner->id)
            ->orderBy('order')
            ->get(['order', 'title']);
            
        // Get existing wide banner (excluding current one если это он) для home_top_wide
        $existingWideBanner = Banner::where('position', 'home_top_wide')
            ->where('is_active', true)
            ->where('id', '!=', $banner->id)
            ->first();
        
        return view('admin.banners.edit', compact('banner', 'positions', 'existingBannersHomeTop', 'existingWideBanner'));
    }

    /**
     * Update the specified resource in storage.
     */
        public function update(Request $request, Banner $banner)
    {
        // Определяем максимальное значение order в зависимости от позиции
        $maxOrder = $request->position === 'home_top_wide' ? 1 : 4;
        
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_uk' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB
            'link' => ['nullable', 'url', 'max:255'],
            'position' => ['required', 'string'],
            'order' => ['required', 'integer', 'min:1', "max:$maxOrder"], // Для home_top_wide только 1, для home_top 1-4
            'is_active' => ['required', 'boolean'],
            'open_new_tab' => ['required', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        // Handle image upload if new image provided
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($banner->image_url) {
                $oldPath = str_replace('/storage/', '', $banner->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Баннер успешно обновлен!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        // Delete image file
        if ($banner->image_url) {
            $path = str_replace('/storage/', '', $banner->image_url);
            Storage::disk('public')->delete($path);
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Баннер успешно удален!');
    }
}
