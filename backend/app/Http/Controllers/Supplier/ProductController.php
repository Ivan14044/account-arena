<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\NotifierService;

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
        $categories = Category::productCategories()->parentCategories()->with('translations')->get();
        
        return view('supplier.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());
        
        // ВАЖНО: Проверяем существование и валидность категории
        if ($request->has('category_id') && $request->category_id) {
            $category = Category::find($request->category_id);
            if (!$category || $category->type !== Category::TYPE_PRODUCT) {
                return back()->withErrors(['category_id' => 'Категория не найдена или неверного типа.'])->withInput();
            }
        }

        // Если выбрана подкатегория, используем её ID как category_id
        if ($request->has('subcategory_id') && !empty($request->input('subcategory_id'))) {
            $subcategory = Category::find($request->input('subcategory_id'));
            if (!$subcategory || $subcategory->type !== Category::TYPE_PRODUCT || !$subcategory->isSubcategory()) {
                return back()->withErrors(['subcategory_id' => 'Подкатегория не найдена или неверного типа.'])->withInput();
            }
            $validated['category_id'] = $request->input('subcategory_id');
        }
        
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
        
        // ВАЖНО: Товары поставщика требуют модерации
        // Устанавливаем статус модерации и деактивируем товар до одобрения
        $validated['moderation_status'] = 'pending';
        $validated['is_active'] = false; // Не показывать до одобрения администратором
        
        $product = ServiceAccount::create($validated);

        // Отправляем уведомление администратору о новом товаре на модерации
        try {
            NotifierService::sendFromTemplate(
                'supplier_product_created',
                'supplier_product_created',
                [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'supplier_name' => auth()->user()->name,
                    'supplier_email' => auth()->user()->email,
                    'price' => number_format($product->price, 2),
                ],
                'info'
            );
        } catch (\Throwable $e) {
            \Log::error('Error sending admin notification for supplier product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('supplier.products.index')
            ->with('success', 'Товар успешно создан и отправлен на модерацию. Он будет доступен после одобрения администратором.');
    }

    private function storeBulkAccounts(Request $request)
    {
        $request->validate([
            'bulk_accounts' => 'required|string',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01', // ВАЖНО: Минимальная цена 0.01 USD
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

        // Если выбрана подкатегория, используем её ID как category_id
        $categoryId = $request->input('subcategory_id') ?: $request->input('category_id');

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
            $product = ServiceAccount::create([
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
                'category_id' => $categoryId,
                'accounts_data' => $accountsList,
                'used' => 0,
                'supplier_id' => auth()->id(),
                // ВАЖНО: Товары поставщика требуют модерации
                'moderation_status' => 'pending',
                'is_active' => false, // Не показывать до одобрения администратором
            ]);

            // Отправляем уведомление администратору о новом товаре на модерации
            try {
                NotifierService::sendFromTemplate(
                    'supplier_product_created',
                    'supplier_product_created',
                    [
                        'product_id' => $product->id,
                        'product_title' => $product->title,
                        'supplier_name' => auth()->user()->name,
                        'supplier_email' => auth()->user()->email,
                        'price' => number_format($product->price, 2),
                        'accounts_count' => count($accountsList),
                    ],
                    'info'
                );
            } catch (\Throwable $e) {
                \Log::error('Error sending admin notification for supplier product (bulk)', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $message = "Товар успешно создан! Аккаунтов в наличии: " . count($accountsList) . ". Товар отправлен на модерацию и будет доступен после одобрения администратором.";
            
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
        
        // Определяем родительскую категорию и подкатегорию
        $parentCategoryId = null;
        $subcategoryId = null;

        if ($product->category_id) {
            $category = Category::find($product->category_id);
            if ($category) {
                if ($category->isSubcategory()) {
                    // Товар привязан к подкатегории
                    $subcategoryId = $category->id;
                    $parentCategoryId = $category->parent_id;
                } else {
                    // Товар привязан к родительской категории
                    $parentCategoryId = $category->id;
                }
            }
        }
        
        $categories = Category::productCategories()->parentCategories()->with('translations')->get();
        
        return view('supplier.products.edit', compact('product', 'parentCategoryId', 'subcategoryId', 'categories'));
    }

    public function update(Request $request, ServiceAccount $product)
    {
        // Проверка, что товар принадлежит этому поставщику
        if ($product->supplier_id !== auth()->id()) {
            abort(403, 'У вас нет доступа к этому товару.');
        }
        
        $validated = $request->validate($this->getRules($product->id));
        
        // ВАЖНО: Проверяем существование и валидность категории
        if ($request->has('category_id') && $request->category_id) {
            $category = Category::find($request->category_id);
            if (!$category || $category->type !== Category::TYPE_PRODUCT) {
                return back()->withErrors(['category_id' => 'Категория не найдена или неверного типа.'])->withInput();
            }
        }

        // Если выбрана подкатегория, используем её ID как category_id
        if ($request->has('subcategory_id') && !empty($request->input('subcategory_id'))) {
            $subcategory = Category::find($request->input('subcategory_id'));
            if (!$subcategory || $subcategory->type !== Category::TYPE_PRODUCT || !$subcategory->isSubcategory()) {
                return back()->withErrors(['subcategory_id' => 'Подкатегория не найдена или неверного типа.'])->withInput();
            }
            $validated['category_id'] = $request->input('subcategory_id');
        }
        
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
     * Экспорт аккаунтов товара
     * Аналогично Admin/ServiceAccountController::export()
     */
    public function export(Request $request, ServiceAccount $product)
    {
        // ВАЖНО: Проверяем, что товар принадлежит этому поставщику
        if ($product->supplier_id !== auth()->id()) {
            abort(403, 'У вас нет доступа к этому товару.');
        }

        // ВАЖНО: Используем транзакцию с блокировкой для предотвращения race condition
        // при одновременном экспорте и покупке товара
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $product) {
            // Блокируем товар для предотвращения race condition
            $product = ServiceAccount::lockForUpdate()->findOrFail($product->id);
            
            // Повторная проверка после блокировки
            if ($product->supplier_id !== auth()->id()) {
                abort(403, 'У вас нет доступа к этому товару.');
            }
            
            $allAccountsData = is_array($product->accounts_data) ? $product->accounts_data : [];

            if (empty($allAccountsData)) {
                return redirect()->route('supplier.products.index')
                    ->with('error', 'Нет товаров для выгрузки');
            }

            // Get current used count (same logic as purchase)
            $usedCount = $product->used ?? 0;

            // ВАЖНО: Проверяем доступное количество после блокировки
            $availableCount = count($allAccountsData) - $usedCount;
            if ($availableCount <= 0) {
                return redirect()->route('supplier.products.index')
                    ->with('error', 'Нет доступных товаров для выгрузки');
            }

            // If count is provided, use it; otherwise export all remaining
            if ($request->has('count')) {
                $count = (int) $request->input('count');
                $exportCount = max(1, min($count, $availableCount));
            } else {
                $exportCount = $availableCount;
            }

            // Get accounts to export starting from used index (same as purchase logic)
            $assignedAccounts = [];
            for ($i = 0; $i < $exportCount; $i++) {
                if (isset($allAccountsData[$usedCount + $i])) {
                    $assignedAccounts[] = $allAccountsData[$usedCount + $i];
                }
            }

            // ВАЖНО: Проверяем, что аккаунты были успешно назначены
            if (empty($assignedAccounts)) {
                \Illuminate\Support\Facades\Log::error('Supplier Product export: No accounts assigned', [
                    'service_account_id' => $product->id,
                    'supplier_id' => auth()->id(),
                    'export_count' => $exportCount,
                    'used_count' => $usedCount,
                    'accounts_data_count' => count($allAccountsData),
                ]);
                return redirect()->route('supplier.products.index')
                    ->with('error', 'Ошибка при выгрузке товаров. Попробуйте еще раз.');
            }

            $content = implode("\n", $assignedAccounts);

            // Ensure UTF-8 encoding with BOM for Windows compatibility
            $content = "\xEF\xBB\xBF" . $content;

            $filename = 'product_' . $product->id . '_' . date('Y-m-d') . '.txt';

            // Increment used count (same as purchase logic - don't remove from array)
            $product->used = $usedCount + $exportCount;
            $product->save();

            // Calculate remaining count
            $remainingCount = count($allAccountsData) - $product->used;

            // Store success message in session for redirect after download
            session()->flash('export_success', 'Выгружено ' . $exportCount . ' товаров. Осталось: ' . $remainingCount);

            return response($content, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        });
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
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
            'price' => ['required', 'numeric', 'min:0.01'], // ВАЖНО: Минимальная цена 0.01 USD
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
