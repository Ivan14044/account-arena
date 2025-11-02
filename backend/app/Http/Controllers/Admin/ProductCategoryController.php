<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::productCategories()->with('translations')->get();

        return view('admin.product-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.product-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $data = ['type' => Category::TYPE_PRODUCT];
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $category = Category::create($data);

        $category->saveTranslation($validated);

        return redirect()->route('admin.product-categories.index')->with('success', 'Категория товаров успешно создана.');
    }

    public function update(Request $request, $product_category)
    {
        $category = Category::where('id', $product_category)
            ->where('type', Category::TYPE_PRODUCT)
            ->firstOrFail();
        
        $validated = $request->validate(
            $this->getRules($category->id),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $category->image_url = Storage::url($path);
            $category->save();
        }

        $category->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.product-categories.edit', $category->id)
            : route('admin.product-categories.index');

        return redirect($route)->with('success', 'Категория товаров успешно обновлена.');
    }

    public function edit($product_category)
    {
        $category = Category::where('id', $product_category)
            ->where('type', Category::TYPE_PRODUCT)
            ->firstOrFail();
        
        $category->load('translations');

        $categoryData = $category->translations
            ->groupBy('locale')
            ->map(function ($translations) {
                return [
                    'name' => optional($translations->firstWhere('code', 'name'))->value,
                    'meta_title' => optional($translations->firstWhere('code', 'meta_title'))->value,
                    'meta_description' => optional($translations->firstWhere('code', 'meta_description'))->value,
                    'text' => optional($translations->firstWhere('code', 'text'))->value,
                ];
            });

        return view('admin.product-categories.edit', compact('category', 'categoryData'));
    }

    public function destroy($product_category)
    {
        $category = Category::where('id', $product_category)
            ->where('type', Category::TYPE_PRODUCT)
            ->firstOrFail();
        
        // Detach products before deleting
        $category->products()->update(['category_id' => null]);
        $category->delete();

        return redirect()->route('admin.product-categories.index')->with('success', 'Категория товаров успешно удалена.');
    }

    private function getRules($id = false)
    {
        $rules = [];

        foreach (config('langs') as $lang => $flag) {
            $rules['name.' . $lang] = ['nullable', 'string', 'max:255'];
            $rules['meta_title.' . $lang] = ['nullable', 'string'];
            $rules['meta_description.' . $lang] = ['nullable', 'string'];
            $rules['text.' . $lang] = ['nullable', 'string'];
        }

        // require at least one language name
        $rules['name'] = ['array', 'required_without_all:' . implode(',', array_map(fn($l) => 'name.' . $l, array_keys(config('langs'))))];
        
        // Image upload rules
        $rules['image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'];

        return $rules;
    }
}

