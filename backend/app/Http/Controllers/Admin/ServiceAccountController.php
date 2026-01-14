<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ServiceAccountController extends Controller
{
    public function index()
    {
        // Сортировка по sort_order (для ручной сортировки), затем по id
        // ВАЖНО: Внедрена пагинация и ограничение полей для оптимизации памяти (исключаем тяжелый accounts_data)
        $serviceAccounts = ServiceAccount::with('category.translations')
            ->select([
                'id', 'sku', 'title', 'price', 'used', 'is_active', 'delivery_type', 
                'category_id', 'supplier_id', 'sort_order', 'created_at'
            ])
            ->selectRaw('JSON_LENGTH(accounts_data) as total_qty_from_json')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Получаем все категории товаров для фильтров и дерева (тут оставляем get, так как категорий обычно немного)
        $categories = \App\Models\Category::productCategories()
            ->with(['translations', 'children.translations'])
            ->orderBy('parent_id', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Группируем категории: родительские и их подкатегории
        $parentCategories = $categories->whereNull('parent_id');
        $subcategories = $categories->whereNotNull('parent_id');

        // ВАЖНО: Получаем статистику по всем товарам для категорий (не ограничиваясь пагинацией)
        $productCounts = ServiceAccount::select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->pluck('count', 'category_id')
            ->toArray();

        // Подсчитываем количество товаров для каждой родительской категории
        $parentCategories = $parentCategories->map(function($category) use ($productCounts, $subcategories) {
            // Товары напрямую в этой категории
            $directCount = $productCounts[$category->id] ?? 0;
            
            // Товары в подкатегориях этой категории
            $subcategoryIds = $subcategories->where('parent_id', $category->id)->pluck('id');
            $subcategoryCount = 0;
            foreach ($subcategoryIds as $subId) {
                $subcategoryCount += $productCounts[$subId] ?? 0;
            }
            
            $category->products_count = $directCount + $subcategoryCount;
            return $category;
        });

        // Подсчитываем количество товаров для каждой подкатегории
        $subcategories = $subcategories->map(function($subcategory) use ($productCounts) {
            $subcategory->products_count = $productCounts[$subcategory->id] ?? 0;
            return $subcategory;
        });

        // Подсчитываем товары без категории
        $noCategoryCount = ServiceAccount::whereNull('category_id')->count();

        return view('admin.service-accounts.index', compact('serviceAccounts', 'parentCategories', 'subcategories', 'noCategoryCount'));
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
            $validated['image_url'] = $path;
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
            'price' => 'required|numeric|min:0.01', // ВАЖНО: Минимальная цена 0.01 USD
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $bulkAccounts = $request->input('bulk_accounts');
        $title = $request->input('title');
        $price = $request->input('price');
        $description = $request->input('description');
        $additionalDescription = $request->input('additional_description');
        
        // ВАЖНО: Проверяем существование и валидность категории
        $categoryId = $request->input('subcategory_id') ?: $request->input('category_id');
        if ($categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if (!$category || $category->type !== \App\Models\Category::TYPE_PRODUCT) {
                return redirect()->back()->withErrors(['category_id' => 'Категория не найдена или неверного типа.'])->withInput();
            }
            // Если выбрана подкатегория, проверяем что это действительно подкатегория
            if ($request->input('subcategory_id') && !$category->isSubcategory()) {
                return redirect()->back()->withErrors(['subcategory_id' => 'Выбранная категория не является подкатегорией.'])->withInput();
            }
        }
        
        $isActive = $request->input('is_active', true);
        $imageUrl = null;

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = $path;
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
                'account_suffix_enabled' => $request->has('account_suffix_enabled') ? (bool) $request->input('account_suffix_enabled') : false,
                'account_suffix_text_ru' => $request->input('account_suffix_text_ru'),
                'account_suffix_text_en' => $request->input('account_suffix_text_en'),
                'account_suffix_text_uk' => $request->input('account_suffix_text_uk'),
                'discount_percent' => $request->input('discount_percent') ? (float) $request->input('discount_percent') : null,
                'discount_start_date' => $request->input('discount_start_date') ? \Carbon\Carbon::parse($request->input('discount_start_date')) : null,
                'discount_end_date' => $request->input('discount_end_date') ? \Carbon\Carbon::parse($request->input('discount_end_date')) : null,
                'delivery_type' => $request->input('delivery_type', 'automatic'),
                'manual_delivery_instructions' => $request->input('manual_delivery_instructions'),
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

        // ВАЖНО: Проверяем существование и валидность категории
        if ($request->has('category_id') && $request->category_id) {
            $category = \App\Models\Category::find($request->category_id);
            if (!$category || $category->type !== \App\Models\Category::TYPE_PRODUCT) {
                return back()->withErrors(['category_id' => 'Категория не найдена или неверного типа.'])->withInput();
            }
        }

        // Если выбрана подкатегория, используем её ID как category_id
        if ($request->has('subcategory_id') && !empty($request->input('subcategory_id'))) {
            $subcategory = \App\Models\Category::find($request->input('subcategory_id'));
            if (!$subcategory || $subcategory->type !== \App\Models\Category::TYPE_PRODUCT || !$subcategory->isSubcategory()) {
                return back()->withErrors(['subcategory_id' => 'Подкатегория не найдена или неверного типа.'])->withInput();
            }
            $validated['category_id'] = $request->input('subcategory_id');
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        // ИСПРАВЛЕНО: Сохраняем проданные аккаунты и добавляем уникальные новые
        $existingAccountsData = is_array($serviceAccount->accounts_data) ? $serviceAccount->accounts_data : [];
        $usedCount = $serviceAccount->used ?? 0;

        // Получаем проданные аккаунты (первые $usedCount элементов)
        $soldAccounts = array_slice($existingAccountsData, 0, $usedCount);

        // ВАЖНО: Фильтруем дубликаты. Новые аккаунты не должны содержать уже проданные 
        // и не должны дублироваться между собой.
        $soldAccountsMap = array_flip($soldAccounts);
        $finalNewAccounts = [];
        $duplicatesCount = 0;

        foreach ($newAccountsList as $account) {
            if (!isset($soldAccountsMap[$account]) && !in_array($account, $finalNewAccounts)) {
                $finalNewAccounts[] = $account;
            } else {
                $duplicatesCount++;
            }
        }

        // Объединяем: проданные аккаунты + отфильтрованные новые аккаунты
        $finalAccountsList = array_merge($soldAccounts, $finalNewAccounts);

        $validated['accounts_data'] = $finalAccountsList;

        // Handle account suffix fields
        $validated['account_suffix_enabled'] = $request->has('account_suffix_enabled') ? (bool) $request->input('account_suffix_enabled') : false;
        $validated['account_suffix_text_ru'] = $request->input('account_suffix_text_ru');
        $validated['account_suffix_text_en'] = $request->input('account_suffix_text_en');
        $validated['account_suffix_text_uk'] = $request->input('account_suffix_text_uk');

        // Handle discount fields
        $validated['discount_percent'] = $request->input('discount_percent') ? (float) $request->input('discount_percent') : null;
        $validated['discount_start_date'] = $request->input('discount_start_date') ? \Carbon\Carbon::parse($request->input('discount_start_date')) : null;
        $validated['discount_end_date'] = $request->input('discount_end_date') ? \Carbon\Carbon::parse($request->input('discount_end_date')) : null;

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
        if (count($finalNewAccounts) > 0) {
            $message .= 'Добавлено новых: ' . count($finalNewAccounts) . '. ';
        }
        if ($duplicatesCount > 0) {
            $message .= 'Отфильтровано дублей: ' . $duplicatesCount . '. ';
        }
        $message .= 'Доступно: ' . (count($finalAccountsList) - $usedCount);

        return redirect($route)->with('success', $message);
    }

    public function export(Request $request, ServiceAccount $serviceAccount)
    {
        // ВАЖНО: Используем транзакцию с блокировкой для предотвращения race condition
        // при одновременном экспорте и покупке товара
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $serviceAccount) {
            // Блокируем товар для предотвращения race condition
            $serviceAccount = ServiceAccount::lockForUpdate()->findOrFail($serviceAccount->id);
            
            $allAccountsData = is_array($serviceAccount->accounts_data) ? $serviceAccount->accounts_data : [];

            if (empty($allAccountsData)) {
                return redirect()->route('admin.service-accounts.index')
                    ->with('error', 'Нет товаров для выгрузки');
            }

            // Get current used count (same logic as purchase)
            $usedCount = $serviceAccount->used ?? 0;

            // ВАЖНО: Проверяем доступное количество после блокировки
            $availableCount = count($allAccountsData) - $usedCount;
            if ($availableCount <= 0) {
                return redirect()->route('admin.service-accounts.index')
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
                \Illuminate\Support\Facades\Log::error('ServiceAccount export: No accounts assigned', [
                    'service_account_id' => $serviceAccount->id,
                    'export_count' => $exportCount,
                    'used_count' => $usedCount,
                    'accounts_data_count' => count($allAccountsData),
                ]);
                return redirect()->route('admin.service-accounts.index')
                    ->with('error', 'Ошибка при выгрузке товаров. Попробуйте еще раз.');
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
        });
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

        if (empty($newAccounts)) {
            return redirect()->back()
                ->with('error', 'Нет данных для импорта. Добавьте хотя бы одну строку.');
        }

        // ВАЖНО: Получаем существующие аккаунты и проверяем на дубликаты
        $existingAccounts = is_array($serviceAccount->accounts_data) ? $serviceAccount->accounts_data : [];
        
        // Создаем массив для быстрой проверки дубликатов
        $existingAccountsMap = array_flip($existingAccounts);
        
        // Фильтруем дубликаты из новых аккаунтов
        $uniqueNewAccounts = [];
        $duplicatesCount = 0;
        foreach ($newAccounts as $account) {
            if (!isset($existingAccountsMap[$account])) {
                $uniqueNewAccounts[] = $account;
                $existingAccountsMap[$account] = true; // Добавляем в карту для проверки дубликатов внутри новых
            } else {
                $duplicatesCount++;
            }
        }

        // Объединяем существующие и уникальные новые аккаунты
        $combinedAccounts = array_merge($existingAccounts, $uniqueNewAccounts);

        $serviceAccount->accounts_data = $combinedAccounts;
        $serviceAccount->save();

        $message = 'Успешно загружено ' . count($uniqueNewAccounts) . ' строк в товар "' . $serviceAccount->title . '". Всего товаров: ' . count($combinedAccounts);
        if ($duplicatesCount > 0) {
            $message .= ' (пропущено дубликатов: ' . $duplicatesCount . ')';
        }

        return redirect()->route('admin.service-accounts.index')
            ->with('success', $message);
    }

    public function destroy(ServiceAccount $serviceAccount)
    {
        $serviceAccount->delete();

        return redirect()->route('admin.service-accounts.index')->with('success', 'Service account successfully deleted.');
    }

    /**
     * Массовые операции с товарами
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,change_price,change_category,change_delivery_type,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:service_accounts,id',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            switch ($action) {
                case 'activate':
                    $updatedCount = ServiceAccount::whereIn('id', $ids)
                        ->update(['is_active' => true]);
                    $message = "Активировано товаров: {$updatedCount}";
                    break;

                case 'deactivate':
                    $updatedCount = ServiceAccount::whereIn('id', $ids)
                        ->update(['is_active' => false]);
                    $message = "Скрыто товаров: {$updatedCount}";
                    break;

                case 'change_price':
                    $actionType = $request->input('action_type');
                    
                    // Валидация в зависимости от типа действия
                    if ($actionType === 'set') {
                        $request->validate([
                            'action_type' => 'required|in:increase,decrease,set',
                            'value' => 'required|numeric|min:0.01',
                        ]);
                    } else {
                        $request->validate([
                            'action_type' => 'required|in:increase,decrease,set',
                            'value' => 'required|numeric|min:0|max:1000',
                        ]);
                    }

                    $value = $request->input('value');
                    $products = ServiceAccount::whereIn('id', $ids)->get();

                    foreach ($products as $product) {
                        $oldPrice = (float)$product->price;
                        $newPrice = $oldPrice;

                        if ($actionType === 'increase') {
                            $newPrice = $oldPrice * (1 + $value / 100);
                        } elseif ($actionType === 'decrease') {
                            $newPrice = $oldPrice * (1 - $value / 100);
                            if ($newPrice < 0.01) {
                                $newPrice = 0.01; // Минимальная цена
                            }
                        } elseif ($actionType === 'set') {
                            $newPrice = $value;
                            if ($newPrice < 0.01) {
                                $newPrice = 0.01; // Минимальная цена
                            }
                        }

                        $product->price = round($newPrice, 2);
                        $product->save();
                        $updatedCount++;
                    }

                    $message = "Цена изменена для {$updatedCount} товаров";
                    break;

                case 'change_category':
                    $request->validate([
                        'category_id' => 'nullable|integer|exists:categories,id',
                    ]);

                    $categoryId = $request->input('category_id');

                    // Проверяем, что категория существует и является категорией товаров
                    if ($categoryId) {
                        $category = \App\Models\Category::find($categoryId);
                        if (!$category || $category->type !== \App\Models\Category::TYPE_PRODUCT) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Категория не найдена или неверного типа'
                            ], 422);
                        }
                    }

                    $updatedCount = ServiceAccount::whereIn('id', $ids)
                        ->update(['category_id' => $categoryId]);
                    $message = "Категория изменена для {$updatedCount} товаров";
                    break;

                case 'change_delivery_type':
                    $request->validate([
                        'delivery_type' => 'required|in:automatic,manual',
                    ]);

                    $deliveryType = $request->input('delivery_type');
                    $updatedCount = ServiceAccount::whereIn('id', $ids)
                        ->update(['delivery_type' => $deliveryType]);
                    $message = "Тип выдачи изменен для {$updatedCount} товаров";
                    break;

                case 'delete':
                    $updatedCount = ServiceAccount::whereIn('id', $ids)->delete();
                    $message = "Удалено товаров: {$updatedCount}";
                    break;

                default:
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Неизвестное действие'
                    ], 422);
            }

            DB::commit();

            // Логируем действие
            \Log::info('Bulk action performed', [
                'admin_id' => auth()->id(),
                'action' => $action,
                'count' => $updatedCount,
                'ids' => $ids
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk action error', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка: ' . $e->getMessage()
            ], 500);
        }
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
            'price' => ['required', 'numeric', 'min:0.01'], // ВАЖНО: Минимальная цена 0.01 USD
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
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:99'], // ВАЖНО: max:99 для соответствия логике в других местах
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date', 'after_or_equal:discount_start_date'],
            'delivery_type' => ['nullable', 'in:automatic,manual'],
            'manual_delivery_instructions' => ['nullable', 'string', 'max:5000'],
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

    /**
     * Обновить порядок сортировки товаров
     */
    public function updateSortOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:service_accounts,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            ServiceAccount::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        // Очистить кеш товаров для клиентов
        Cache::forget('active_accounts_list');

        return response()->json(['success' => true, 'message' => 'Порядок сортировки обновлен']);
    }

    /**
     * Применить сортировку ко всем товарам (обновить sort_order)
     */
    public function applySortOrder(Request $request)
    {
        $request->validate([
            'sort_by' => 'required|string|in:id,price,created_at',
            'direction' => 'required|string|in:asc,desc',
        ]);

        $sortBy = $request->sort_by;
        $direction = $request->direction;

        // Получаем все товары, отсортированные по выбранному полю
        $serviceAccounts = ServiceAccount::orderBy($sortBy, $direction)->get();

        // Оптимизированное массовое обновление sort_order
        $updates = [];
        foreach ($serviceAccounts as $index => $account) {
            $updates[] = [
                'id' => $account->id,
                'sort_order' => $index + 1,
            ];
        }

        // Массовое обновление через транзакцию
        DB::transaction(function () use ($updates) {
            foreach ($updates as $update) {
                ServiceAccount::where('id', $update['id'])
                    ->update(['sort_order' => $update['sort_order']]);
            }
        });

        // Очистить кеш товаров для клиентов
        Cache::forget('active_accounts_list');

        return response()->json([
            'success' => true, 
            'message' => 'Сортировка применена и сохранена'
        ]);
    }
}
