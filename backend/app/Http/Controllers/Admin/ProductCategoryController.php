<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductCategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        // Получаем родительские категории товаров с переводами и количеством подкатегорий и товаров
        $categories = Category::productCategories()
            ->parentCategories()
            ->with(['translations', 'children.translations'])
            ->withCount(['children', 'products'])
            ->get();

        // Подсчитываем общее количество товаров в каждой категории (включая товары в подкатегориях)
        $categories->each(function($category) {
            $childProductsCount = Category::where('parent_id', $category->id)
                ->withCount('products')
                ->get()
                ->sum('products_count');
            $category->total_products_count = $category->products_count + $childProductsCount;
        });

        // Общая статистика для карточек
        $stats = [
            'total_categories' => $categories->count(),
            'total_subcategories' => Category::productCategories()->subcategories()->count(),
            'total_products' => ServiceAccount::count(),
        ];

        return view('admin.product-categories.index', compact('categories', 'stats'));
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

        $data = [
            'type' => Category::TYPE_PRODUCT,
            'parent_id' => null,
        ];
        
        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('categories', 'public');
        }

        $this->categoryService->saveCategory($data, $validated);

        return redirect()->route('admin.product-categories.index')->with('success', 'Категория товаров успешно создана.');
    }

    public function update(Request $request, $product_category)
    {
        $category = Category::where('id', $product_category)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNull('parent_id')
            ->firstOrFail();
        
        $validated = $request->validate(
            $this->getRules($category->id),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        $data = [];
        if ($request->hasFile('image')) {
            // Удаляем старое изображение, если оно существует
            if ($category->image_url) {
                $oldImagePath = $category->getRawOriginal('image_url');
                if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
            $data['image_url'] = $request->file('image')->store('categories', 'public');
        }

        $this->categoryService->saveCategory($data, $validated, $category);

        $route = $request->has('save')
            ? route('admin.product-categories.edit', $category->id)
            : route('admin.product-categories.index');

        return redirect($route)->with('success', 'Категория товаров успешно обновлена.');
    }

    public function edit($product_category)
    {
        $category = Category::where('id', $product_category)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNull('parent_id')
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

        return view('admin.product-categories.edit', compact('category', 'categoryData'));
    }

    public function destroy($product_category)
    {
        $category = Category::where('id', $product_category)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNull('parent_id')
            ->firstOrFail();
        
        $result = $this->categoryService->deleteCategory($category);

        if (!$result['success']) {
            return redirect()->route('admin.product-categories.index')->with('error', $result['message']);
        }

        return redirect()->route('admin.product-categories.index')->with('success', $result['message']);
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
        $rules['image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'];

        return $rules;
    }
}
