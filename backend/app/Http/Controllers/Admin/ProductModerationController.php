<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\SupplierNotification;
use App\Services\NotifierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductModerationController extends Controller
{
    /**
     * Список товаров на модерации
     */
    public function index()
    {
        $products = ServiceAccount::with(['supplier', 'category'])
            ->pendingModeration()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.product-moderation.index', compact('products'));
    }

    /**
     * Просмотр товара для модерации
     */
    public function show(ServiceAccount $product)
    {
        // Проверяем, что товар требует модерации
        if (!$product->requiresModeration()) {
            return redirect()->route('admin.product-moderation.index')
                ->with('error', 'Этот товар не требует модерации.');
        }

        // Загружаем связи
        $product->load(['supplier', 'category', 'moderator']);

        // Получаем аккаунты для предпросмотра и валидации
        $accountsData = is_array($product->accounts_data) ? $product->accounts_data : [];
        $totalAccounts = count($accountsData);
        
        $stats = [
            'total' => $totalAccounts,
            'valid' => 0,
            'invalid' => 0,
            'errors' => []
        ];

        // ВАЖНО: Полная валидация всех строк (Storage DOS & Fraud protection)
        foreach ($accountsData as $index => $line) {
            $line = trim((string)$line);
            if (empty($line)) {
                $stats['invalid']++;
                if (count($stats['errors']) < 10) {
                    $stats['errors'][] = "Строка " . ($index + 1) . ": Пустая строка";
                }
                continue;
            }
            
            // Базовая проверка формата (наличие разделителя)
            if (preg_match('/[:;|]/', $line)) {
                $stats['valid']++;
            } else {
                $stats['invalid']++;
                if (count($stats['errors']) < 10) {
                    $stats['errors'][] = "Строка " . ($index + 1) . ": Неверный формат (нет разделителя : или ; или |)";
                }
            }
        }

        $previewAccounts = array_slice($accountsData, 0, 50);

        return view('admin.product-moderation.show', compact('product', 'previewAccounts', 'totalAccounts', 'stats'));
    }

    /**
     * Одобрить товар
     */
    public function approve(ServiceAccount $product)
    {
        // Проверяем, что товар на модерации
        if ($product->moderation_status !== 'pending') {
            return back()->with('error', 'Товар уже обработан.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($product) {
                // ВАЖНО: Блокируем товар для предотвращения race condition
                $lockedProduct = ServiceAccount::lockForUpdate()->findOrFail($product->id);
                
                // Проверяем, что товар еще на модерации (после блокировки)
                if ($lockedProduct->moderation_status !== 'pending') {
                    throw new \Exception('Товар уже обработан другим администратором.');
                }

                // ВАЖНО: Дополнительная валидация критических полей перед одобрением
                // Это предотвращает публикацию товаров с некорректными данными, 
                // которые могли быть установлены в обход валидации при создании.
                if (empty($lockedProduct->title)) {
                    throw new \Exception('Критическая ошибка: Товар не имеет названия.');
                }

                if ($lockedProduct->price === null || $lockedProduct->price < 0.01) {
                    throw new \Exception('Критическая ошибка: Цена товара должна быть больше нуля.');
                }

                if (!$lockedProduct->category_id) {
                    throw new \Exception('Критическая ошибка: Товар не привязан к категории.');
                }

                if (empty($lockedProduct->accounts_data) || !is_array($lockedProduct->accounts_data)) {
                    throw new \Exception('Критическая ошибка: Товар не содержит данных аккаунтов.');
                }
                
                // Одобряем товар
                $lockedProduct->update([
                    'moderation_status' => 'approved',
                    'is_active' => true, // Активируем товар
                    'moderated_at' => now(),
                    'moderated_by' => auth()->id(),
                ]);
                
                // Обновляем объект для дальнейшего использования
                $product->refresh();

                Log::info('Product approved by admin', [
                    'product_id' => $lockedProduct->id,
                    'admin_id' => auth()->id(),
                    'supplier_id' => $lockedProduct->supplier_id,
                ]);
            });

            // ВАЖНО: Уведомления вынесены за пределы транзакции
            if ($product->supplier_id && $product->supplier) {
                try {
                    SupplierNotification::create([
                        'user_id' => $product->supplier_id,
                        'type' => 'product_approved',
                        'title' => 'Товар одобрен',
                        'message' => "Ваш товар \"{$product->title}\" был одобрен администратором и теперь доступен для продажи.",
                        'data' => [
                            'product_id' => $product->id,
                            'product_title' => $product->title,
                        ],
                    ]);

                    // Также отправляем через NotifierService (если есть шаблон)
                    try {
                        NotifierService::sendFromTemplate(
                            'supplier_product_approved',
                            'supplier_product_approved',
                            [
                                'product_id' => $product->id,
                                'product_title' => $product->title,
                            ],
                            'success'
                        );
                    } catch (\Throwable $e) {
                        Log::warning('ProductModerationController: Template notification failed', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('ProductModerationController: Failed to send supplier notification', [
                        'product_id' => $product->id,
                        'supplier_id' => $product->supplier_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()->route('admin.product-moderation.index')
                ->with('success', 'Товар успешно одобрен.');
        } catch (\Exception $e) {
            Log::error('ProductModerationController: Failed to approve product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ошибка при одобрении товара: ' . $e->getMessage());
        }
    }

    /**
     * Отклонить товар
     */
    public function reject(Request $request, ServiceAccount $product)
    {
        // Проверяем, что товар на модерации
        if ($product->moderation_status !== 'pending') {
            return back()->with('error', 'Товар уже обработан.');
        }

        $validated = $request->validate([
            'moderation_comment' => 'required|string|max:1000',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($product, $validated) {
                // ВАЖНО: Блокируем товар для предотвращения race condition
                $lockedProduct = ServiceAccount::lockForUpdate()->findOrFail($product->id);
                
                // Проверяем, что товар еще на модерации (после блокировки)
                if ($lockedProduct->moderation_status !== 'pending') {
                    throw new \Exception('Товар уже обработан другим администратором.');
                }
                
                // Отклоняем товар
                $lockedProduct->update([
                    'moderation_status' => 'rejected',
                    'is_active' => false, // Деактивируем товар
                    'moderation_comment' => $validated['moderation_comment'],
                    'moderated_at' => now(),
                    'moderated_by' => auth()->id(),
                ]);
                
                // Обновляем объект для дальнейшего использования
                $product->refresh();

                Log::info('Product rejected by admin', [
                    'product_id' => $lockedProduct->id,
                    'admin_id' => auth()->id(),
                    'supplier_id' => $lockedProduct->supplier_id,
                    'comment' => $validated['moderation_comment'],
                ]);
            });

            // ВАЖНО: Уведомления вынесены за пределы транзакции
            if ($product->supplier_id && $product->supplier) {
                try {
                    SupplierNotification::create([
                        'user_id' => $product->supplier_id,
                        'type' => 'product_rejected',
                        'title' => 'Товар отклонен',
                        'message' => "Ваш товар \"{$product->title}\" был отклонен администратором. Причина: {$validated['moderation_comment']}",
                        'data' => [
                            'product_id' => $product->id,
                            'product_title' => $product->title,
                            'comment' => $validated['moderation_comment'],
                        ],
                    ]);

                    // Также отправляем через NotifierService (если есть шаблон)
                    try {
                        NotifierService::sendFromTemplate(
                            'supplier_product_rejected',
                            'supplier_product_rejected',
                            [
                                'product_id' => $product->id,
                                'product_title' => $product->title,
                                'comment' => $validated['moderation_comment'],
                            ],
                            'danger'
                        );
                    } catch (\Throwable $e) {
                        Log::warning('ProductModerationController: Template notification failed', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('ProductModerationController: Failed to send supplier notification', [
                        'product_id' => $product->id,
                        'supplier_id' => $product->supplier_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()->route('admin.product-moderation.index')
                ->with('success', 'Товар успешно отклонен. Поставщик получит уведомление.');
        } catch (\Exception $e) {
            Log::error('ProductModerationController: Failed to reject product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ошибка при отклонении товара: ' . $e->getMessage());
        }
    }
}
