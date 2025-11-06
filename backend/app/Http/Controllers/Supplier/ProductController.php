<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $supplier = auth()->user();
        $products = $supplier->supplierProducts()->with('category')->orderBy('created_at', 'desc')->get();
        
        return view('supplier.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::productCategories()->with('translations')->get();
        
        return view('supplier.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }
        
        // Check if bulk accounts are provided
        if ($request->has('bulk_accounts') && !empty(trim($request->input('bulk_accounts')))) {
            return $this->storeBulkAccounts($request);
        }

        // Single product creation
        $accountsList = [];
        $validated['accounts_data'] = $accountsList;
        $validated['used'] = 0;
        $validated['supplier_id'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', false);
        
        ServiceAccount::create($validated);

        return redirect()->route('supplier.products.index')->with('success', 'Товар успешно создан.');
    }

    private function storeBulkAccounts(Request $request)
    {
        $request->validate([
            'bulk_accounts' => 'required|string',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $bulkAccounts = $request->input('bulk_accounts');
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = Storage::url($path);
        }

        $lines = array_filter(explode("\n", $bulkAccounts));
        $accountsList = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $accountsList[] = $line;
            }
        }

        if (empty($accountsList)) {
            return redirect()->back()->withErrors(['bulk_accounts' => 'Добавьте хотя бы один аккаунт']);
        }

        try {
            ServiceAccount::create([
                'title' => $request->input('title'),
                'title_en' => $request->input('title_en'),
                'title_uk' => $request->input('title_uk'),
                'price' => (float) $request->input('price'),
                'description' => $request->input('description'),
                'description_en' => $request->input('description_en'),
                'description_uk' => $request->input('description_uk'),
                'additional_description' => $request->input('additional_description'),
                'additional_description_en' => $request->input('additional_description_en'),
                'additional_description_uk' => $request->input('additional_description_uk'),
                'image_url' => $imageUrl,
                'category_id' => $request->input('category_id'),
                'accounts_data' => $accountsList,
                'used' => 0,
                'is_active' => $request->boolean('is_active', false),
                'supplier_id' => auth()->id(),
            ]);

            $message = "Товар успешно создан! Аккаунтов в наличии: " . count($accountsList);
            
            return redirect()->route('supplier.products.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Ошибка при создании товара: ' . $e->getMessage()]);
        }
    }

    public function edit(ServiceAccount $product)
    {
        // Проверка, что товар принадлежит этому поставщику
        if ($product->supplier_id !== auth()->id()) {
            abort(403, 'У вас нет доступа к этому товару.');
        }
        
        $categories = Category::productCategories()->with('translations')->get();
        
        return view('supplier.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, ServiceAccount $product)
    {
        // Проверка, что товар принадлежит этому поставщику
        if ($product->supplier_id !== auth()->id()) {
            abort(403, 'У вас нет доступа к этому товару.');
        }
        
        $validated = $request->validate($this->getRules($product->id));
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }
        
        $validated['is_active'] = $request->boolean('is_active', false);
        
        // Handle bulk accounts if provided - add to existing
        if ($request->has('bulk_accounts') && !empty(trim($request->input('bulk_accounts')))) {
            $newAccounts = $request->input('bulk_accounts');
            $lines = array_filter(explode("\n", $newAccounts));
            $newAccountsList = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $newAccountsList[] = $line;
                }
            }
            
            if (!empty($newAccountsList)) {
                $existingAccounts = $product->accounts_data ?? [];
                $validated['accounts_data'] = array_merge($existingAccounts, $newAccountsList);
            }
        }
        
        $product->update($validated);

        return redirect()->route('supplier.products.index')->with('success', 'Товар успешно обновлен.');
    }

    public function destroy(ServiceAccount $product)
    {
        // Проверка, что товар принадлежит этому поставщику
        if ($product->supplier_id !== auth()->id()) {
            abort(403, 'У вас нет доступа к этому товару.');
        }
        
        $product->delete();

        return redirect()->route('supplier.products.index')->with('success', 'Товар успешно удален.');
    }

    /**
     * Upload image for CKEditor
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $path = $file->store('products/descriptions', 'public');
            $url = Storage::url($path);

            return response()->json([
                'url' => $url,
                'uploaded' => 1,
                'fileName' => $file->getClientOriginalName(),
            ]);
        }

        return response()->json([
            'uploaded' => 0,
            'error' => [
                'message' => 'Не удалось загрузить изображение'
            ]
        ]);
    }

    private function getRules($id = false)
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'title_uk' => ['nullable', 'string', 'max:255'],
            'description_uk' => ['nullable', 'string'],
            'additional_description' => ['nullable', 'string'],
            'additional_description_en' => ['nullable', 'string'],
            'additional_description_uk' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'bulk_accounts' => ['nullable', 'string'],
        ];
    }
}
