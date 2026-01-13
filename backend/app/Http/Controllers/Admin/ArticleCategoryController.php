<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class ArticleCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::articleCategories()->with('translations')->get();

        return view('admin.article-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.article-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $category = Category::create(['type' => Category::TYPE_ARTICLE]);

        $category->saveTranslation($validated);

        return redirect()->route('admin.article-categories.index')->with('success', 'Категория статей успешно создана.');
    }

    public function update(Request $request, $article_category)
    {
        $category = Category::where('id', $article_category)
            ->articleCategories()
            ->firstOrFail();

        $validated = $request->validate(
            $this->getRules($category->id),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $category->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.article-categories.edit', $category->id)
            : route('admin.article-categories.index');

        return redirect($route)->with('success', 'Категория статей успешно обновлена.');
    }

    public function edit($article_category)
    {
        $category = Category::where('id', $article_category)
            ->articleCategories()
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

        return view('admin.article-categories.edit', compact('category', 'categoryData'));
    }

    public function destroy($article_category)
    {
        $category = Category::where('id', $article_category)
            ->articleCategories()
            ->firstOrFail();

        $category->articles()->detach();
        $category->delete();

        return redirect()->route('admin.article-categories.index')->with('success', 'Категория статей успешно удалена.');
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

