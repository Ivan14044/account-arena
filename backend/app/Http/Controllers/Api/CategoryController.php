<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $type = $request->input('type'); // 'product' or 'article'
        $categories = $this->categoryService->getCategories($type);

        return CategoryResource::collection($categories);
    }

    /**
     * Получить подкатегории по родительской категории
     */
    public function getSubcategories(Request $request, $categoryId)
    {
        $subcategories = $this->categoryService->getSubcategories($categoryId);

        return CategoryResource::collection($subcategories);
    }
}
