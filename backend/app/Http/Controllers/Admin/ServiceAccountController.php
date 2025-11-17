<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ServiceAccountController extends Controller
{
    public function index()
    {
        $serviceAccounts = ServiceAccount::orderBy('id', 'desc')->get();

        return view('admin.service-accounts.index', compact('serviceAccounts'));
    }

    public function create()
    {
        return view('admin.service-accounts.create');
    }

    public function store(Request $request)
    {
        // Handle image upload first
        $validated = $request->validate($this->getRules());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        // Если выбрана подкатегория, используем её ID как category_id
        if ($request->has('subcategory_id') && !empty($request->input('subcategory_id'))) {
            $validated['category_id'] = $request->input('subcategory_id');
        }

        // Check if bulk accounts are provided (non-empty)
        if ($request->has('bulk_accounts') && !empty(trim($request->input('bulk_accounts')))) {
            return $this->storeBulkAccounts($request);
        }

        // Single product creation - always initialize accounts_data as array (even if empty)
        // This ensures the product can be displayed on frontend and edited later
        $accountsList = [];
        $validated['accounts_data'] = $accountsList;
        $validated['used'] = 0;

        ServiceAccount::create($validated);

        return redirect()->route('admin.service-accounts.index')->with('success', 'Товар успешно создан.');
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
        $title = $request->input('title');
        $price = $request->input('price');
        $description = $request->input('description');
        $additionalDescription = $request->input('additional_description');
        // Если выбрана подкатегория, используем её ID как category_id
        $categoryId = $request->input('subcategory_id') ?: $request->input('category_id');
        $isActive = $request->input('is_active', true);
        $imageUrl = null;

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = Storage::url($path);
        }

        $lines = array_filter(explode("\n", $bulkAccounts));
        $accountsList = [];

        // Collect all account lines
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $accountsList[] = $line;
            }
        }

        if (empty($accountsList)) {
            return redirect()->back()->withErrors(['bulk_accounts' => 'Добавьте хотя бы один аккаунт']);
        }

        // Create ONE product with all accounts
        try {
            ServiceAccount::create([
                'title' => $title,
                'title_en' => $request->input('title_en'),
                'title_uk' => $request->input('title_uk'),
                // sku генерируется автоматически в модели
                'price' => (float) $price,
                'description' => $description,
                'description_en' => $request->input('description_en'),
                'description_uk' => $request->input('description_uk'),
                'additional_description' => $additionalDescription,
                'additional_description_en' => $request->input('additional_description_en'),
                'additional_description_uk' => $request->input('additional_description_uk'),
                'meta_title' => $request->input('meta_title'),
                'meta_title_en' => $request->input('meta_title_en'),
                'meta_title_uk' => $request->input('meta_title_uk'),
                'meta_description' => $request->input('meta_description'),
                'meta_description_en' => $request->input('meta_description_en'),
                'meta_description_uk' => $request->input('meta_description_uk'),
                'image_url' => $imageUrl,
                'category_id' => $categoryId,
                'accounts_data' => $accountsList,
                'used' => 0,
                'is_active' => $isActive,
                'service_id' => null,
            ]);

            $message = "Товар успешно создан! Аккаунтов в наличии: " . count($accountsList);

            return redirect()->route('admin.service-accounts.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Ошибка при создании товара: ' . $e->getMessage()]);
        }
    }

    public function edit(ServiceAccount $serviceAccount)
    {
        // Определяем родительскую категорию и подкатегорию
        $parentCategoryId = null;
        $subcategoryId = null;

        if ($serviceAccount->category_id) {
            $category = \App\Models\Category::find($serviceAccount->category_id);
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

        return view('admin.service-accounts.edit', compact('serviceAccount', 'parentCategoryId', 'subcategoryId'));
    }

    public function update(Request $request, ServiceAccount $serviceAccount)
    {
        // ИСПРАВЛЕНО: Правильная обработка аккаунтов с сохранением проданных
        $newAccountsList = [];
        if ($request->has('accounts_data')) {
            $accountsData = $request->input('accounts_data');
            $lines = array_filter(explode("\n", $accountsData));

            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $newAccountsList[] = $line;
                }
            }
        }

        // Validate without accounts_data
        $validationRules = $this->getRules($serviceAccount->id);
        unset($validationRules['accounts_data']);
        $validated = $request->validate($validationRules);

        // Если выбрана подкатегория, используем её ID как category_id
        if ($request->has('subcategory_id') && !empty($request->input('subcategory_id'))) {
            $validated['category_id'] = $request->input('subcategory_id');
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        // ИСПРАВЛЕНО: Сохраняем проданные аккаунты и добавляем новые
        $existingAccountsData = is_array($serviceAccount->accounts_data) ? $serviceAccount->accounts_data : [];
        $usedCount = $serviceAccount->used ?? 0;

        // Получаем проданные аккаунты (первые $usedCount элементов)
        $soldAccounts = array_slice($existingAccountsData, 0, $usedCount);

        // Объединяем: проданные аккаунты + новые аккаунты
        $finalAccountsList = array_merge($soldAccounts, $newAccountsList);

        $validated['accounts_data'] = $finalAccountsList;

        // Логируем для отладки
        \Log::info('Service Account updated', [
            'id' => $serviceAccount->id,
            'sold_accounts' => count($soldAccounts),
            'new_accounts' => count($newAccountsList),
            'total_accounts' => count($finalAccountsList),
            'used' => $usedCount,
        ]);

        $serviceAccount->update($validated);

        $route = $request->has('save')
            ? route('admin.service-accounts.edit', $serviceAccount->id)
            : route('admin.service-accounts.index');

        $message = 'Товар успешно обновлен. ';
        if (count($newAccountsList) > 0) {
            $message .= 'Добавлено новых аккаунтов: ' . count($newAccountsList) . '. ';
        }
        $message .= 'Доступно: ' . (count($finalAccountsList) - $usedCount);

        return redirect($route)->with('success', $message);
    }

    public function export(Request $request, ServiceAccount $serviceAccount)
    {
        $allAccountsData = is_array($serviceAccount->accounts_data) ? $serviceAccount->accounts_data : [];

        if (empty($allAccountsData)) {
            return redirect()->route('admin.service-accounts.index')
                ->with('error', 'Нет товаров для выгрузки');
        }

        // Get current used count (same logic as purchase)
        $usedCount = $serviceAccount->used ?? 0;
        
        // If count is provided, use it; otherwise export all remaining
        if ($request->has('count')) {
            $count = (int) $request->input('count');
            $availableCount = count($allAccountsData) - $usedCount;
            $exportCount = max(1, min($count, $availableCount));
        } else {
            $exportCount = count($allAccountsData) - $usedCount;
        }

        // Get accounts to export starting from used index (same as purchase logic)
        $assignedAccounts = [];
        for ($i = 0; $i < $exportCount; $i++) {
            if (isset($allAccountsData[$usedCount + $i])) {
                $assignedAccounts[] = $allAccountsData[$usedCount + $i];
            }
        }

        $content = implode("\n", $assignedAccounts);

        // Ensure UTF-8 encoding with BOM for Windows compatibility
        $content = "\xEF\xBB\xBF" . $content;

        $filename = 'product_' . $serviceAccount->id . '_' . date('Y-m-d') . '.txt';

        // Increment used count (same as purchase logic - don't remove from array)
        $serviceAccount->used = $usedCount + $exportCount;
        $serviceAccount->save();

        // Calculate remaining count
        $remainingCount = count($allAccountsData) - $serviceAccount->used;

        // Store success message in session for redirect after download
        session()->flash('export_success', 'Выгружено ' . $exportCount . ' товаров. Осталось: ' . $remainingCount);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(Request $request, ServiceAccount $serviceAccount)
    {
        $request->validate([
            'import_data' => 'required|string',
        ]);

        $importData = $request->input('import_data');
        $importLines = array_filter(explode("\n", $importData));

        $newAccounts = [];
        foreach ($importLines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $newAccounts[] = $line;
            }
        }

        $existingAccounts = is_array($serviceAccount->accounts_data) ? $serviceAccount->accounts_data : [];
        $combinedAccounts = array_merge($existingAccounts, $newAccounts);

        $serviceAccount->accounts_data = $combinedAccounts;
        $serviceAccount->save();

        return redirect()->route('admin.service-accounts.index')
            ->with('success', 'Успешно загружено ' . count($newAccounts) . ' строк в товар "' . $serviceAccount->title . '". Всего товаров: ' . count($combinedAccounts));
    }

    public function destroy(ServiceAccount $serviceAccount)
    {
        $serviceAccount->delete();

        return redirect()->route('admin.service-accounts.index')->with('success', 'Service account successfully deleted.');
    }

    private function getRules($id = null): array
    {
        return [
            'service_id' => ['nullable'], // Services are no longer supported
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            // sku убран из правил валидации - генерируется автоматически
            'profile_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            'credentials' => ['nullable', 'array'],
            'expiring_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'title_uk' => ['nullable', 'string', 'max:255'],
            'description_uk' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'additional_description' => ['nullable', 'string'],
            'additional_description_en' => ['nullable', 'string'],
            'additional_description_uk' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_title_uk' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_description_en' => ['nullable', 'string'],
            'meta_description_uk' => ['nullable', 'string'],
            'accounts_data' => ['nullable', 'string'],
        ];
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
}
