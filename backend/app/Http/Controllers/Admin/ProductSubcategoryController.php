<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductSubcategoryController extends Controller
{
    public function index()
    {
        // Получаем все подкатегории товаров с их родительскими категориями
        $subcategories = Category::productCategories()
            ->subcategories()
            ->with(['parent.translations', 'translations'])
            ->get();

        return view('admin.product-subcategories.index', compact('subcategories'));
    }

    public function create()
    {
        // Получаем все родительские категории товаров для выбора
        $parentCategories = Category::productCategories()
            ->parentCategories()
            ->with('translations')
            ->get();

        return view('admin.product-subcategories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        // Проверяем, что выбранная родительская категория существует и является категорией товаров
        $parentCategory = Category::where('id', $request->parent_id)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNull('parent_id') // Родительская категория не должна быть подкатегорией
            ->firstOrFail();

        $data = [
            'type' => Category::TYPE_PRODUCT,
            'parent_id' => $parentCategory->id,
            // Подкатегории не имеют изображений
            'image_url' => null,
        ];

        $subcategory = Category::create($data);
        $subcategory->saveTranslation($validated);

        return redirect()->route('admin.product-subcategories.index')->with('success', 'Подкатегория товаров успешно создана.');
    }

    public function update(Request $request, $product_subcategory)
    {
        $subcategory = Category::where('id', $product_subcategory)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNotNull('parent_id')
            ->firstOrFail();
        
        $validated = $request->validate(
            $this->getRules($subcategory->id),
            [],
            getTransAttributes(['name', 'meta_title', 'meta_description', 'text'])
        );

        // Проверяем, что выбранная родительская категория существует и является категорией товаров
        if ($request->parent_id != $subcategory->parent_id) {
            $parentCategory = Category::where('id', $request->parent_id)
                ->where('type', Category::TYPE_PRODUCT)
                ->whereNull('parent_id')
                ->firstOrFail();
            
            $subcategory->parent_id = $parentCategory->id;
            $subcategory->save();
        }

        $subcategory->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.product-subcategories.edit', $subcategory->id)
            : route('admin.product-subcategories.index');

        return redirect($route)->with('success', 'Подкатегория товаров успешно обновлена.');
    }

    public function edit($product_subcategory)
    {
        $subcategory = Category::where('id', $product_subcategory)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNotNull('parent_id')
            ->firstOrFail();
        
        $subcategory->load(['parent.translations', 'translations']);

        $subcategoryData = $subcategory->translations
            ->groupBy('locale')
            ->map(function ($translations) {
                return [
                    'name' => optional($translations->firstWhere('code', 'name'))->value,
                    'meta_title' => optional($translations->firstWhere('code', 'meta_title'))->value,
                    'meta_description' => optional($translations->firstWhere('code', 'meta_description'))->value,
                    'text' => optional($translations->firstWhere('code', 'text'))->value,
                ];
            });

        // Получаем все родительские категории товаров для выбора
        $parentCategories = Category::productCategories()
            ->parentCategories()
            ->with('translations')
            ->get();

        return view('admin.product-subcategories.edit', compact('subcategory', 'subcategoryData', 'parentCategories'));
    }

    public function destroy($product_subcategory)
    {
        $subcategory = Category::where('id', $product_subcategory)
            ->where('type', Category::TYPE_PRODUCT)
            ->whereNotNull('parent_id')
            ->firstOrFail();
        
        // Отвязываем товары от подкатегории перед удалением
        $subcategory->products()->update(['category_id' => null]);
        $subcategory->delete();

        return redirect()->route('admin.product-subcategories.index')->with('success', 'Подкатегория товаров успешно удалена.');
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
        
        // Родительская категория обязательна
        $rules['parent_id'] = ['required', 'exists:categories,id'];

        return $rules;
    }
}

