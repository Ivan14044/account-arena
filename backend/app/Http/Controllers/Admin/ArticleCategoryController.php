<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class ArticleCategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        $categories = $this->categoryService->getCategories(Category::TYPE_ARTICLE);

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

        $this->categoryService->saveCategory(['type' => Category::TYPE_ARTICLE], $validated);

        return redirect()->route('admin.article-categories.index')->with('success', 'Категория статей успешно создана.');
    }

    public function update(Request $request, $article_category)
    {
        $category = Category::where('id', $article_category)
            ->where('type', Category::TYPE_ARTICLE)
            ->firstOrFail();

        $validated = $request->validate(
            $this->getRules($category->id),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $this->categoryService->saveCategory([], $validated, $category);

        $route = $request->has('save')
            ? route('admin.article-categories.edit', $category->id)
            : route('admin.article-categories.index');

        return redirect($route)->with('success', 'Категория статей успешно обновлена.');
    }

    public function edit($article_category)
    {
        $category = Category::where('id', $article_category)
            ->where('type', Category::TYPE_ARTICLE)
            ->with('translations')
            ->firstOrFail();

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
            ->where('type', Category::TYPE_ARTICLE)
            ->firstOrFail();

        $this->categoryService->deleteCategory($category);

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

        $rules['name'] = ['array', 'required_without_all:' . implode(',', array_map(fn($l) => 'name.' . $l, array_keys(config('langs'))))];

        return $rules;
    }
}
