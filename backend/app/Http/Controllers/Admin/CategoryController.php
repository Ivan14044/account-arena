<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'article'); // Default to article for backward compatibility
        
        $categories = Category::where('type', $type)->with('translations')->get();

        return view('admin.categories.index', compact('categories', 'type'));
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'article'); // Default to article for backward compatibility
        
        return view('admin.categories.create', compact('type'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $type = $request->get('type', 'article'); // Default to article for backward compatibility
        
        $category = Category::create(['type' => $type]);

        $category->saveTranslation($validated);

        return redirect()->route('admin.categories.index', ['type' => $type])->with('success', 'Категория успешно создана.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate(
            $this->getRules($category->id),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $category->saveTranslation($validated);

        $type = $category->type ?? 'article';
        
        $route = $request->has('save')
            ? route('admin.categories.edit', $category->id)
            : route('admin.categories.index', ['type' => $type]);

        return redirect($route)->with('success', 'Категория успешно обновлена.');
    }

    public function edit(Category $category)
    {
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

        return view('admin.categories.edit', compact('category', 'categoryData'));
    }

    public function destroy(Request $request, Category $category)
    {
        $type = $category->type ?? 'article';
        
        $category->articles()->detach();
        $category->delete();

        return redirect()->route('admin.categories.index', ['type' => $type])->with('success', 'Категория успешно удалена.');
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

        return $rules;
    }
}
