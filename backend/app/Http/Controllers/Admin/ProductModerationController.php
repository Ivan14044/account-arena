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

        // Получаем первые 10 аккаунтов для предпросмотра
        $accountsData = is_array($product->accounts_data) ? $product->accounts_data : [];
        $previewAccounts = array_slice($accountsData, 0, 10);
        $totalAccounts = count($accountsData);

        return view('admin.product-moderation.show', compact('product', 'previewAccounts', 'totalAccounts'));
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
                // Одобряем товар
                $product->update([
                    'moderation_status' => 'approved',
                    'is_active' => true, // Активируем товар
                    'moderated_at' => now(),
                    'moderated_by' => auth()->id(),
                ]);

                // Отправляем уведомление поставщику
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
                            // Игнорируем ошибки шаблона, так как уведомление уже отправлено через SupplierNotification
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
                        // Не прерываем транзакцию, если уведомление не отправилось
                    }
                }

                Log::info('Product approved by admin', [
                    'product_id' => $product->id,
                    'admin_id' => auth()->id(),
                    'supplier_id' => $product->supplier_id,
                ]);
            });

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
                // Отклоняем товар
                $product->update([
                    'moderation_status' => 'rejected',
                    'is_active' => false, // Деактивируем товар
                    'moderation_comment' => $validated['moderation_comment'],
                    'moderated_at' => now(),
                    'moderated_by' => auth()->id(),
                ]);

                // Отправляем уведомление поставщику
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
                            // Игнорируем ошибки шаблона, так как уведомление уже отправлено через SupplierNotification
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
                        // Не прерываем транзакцию, если уведомление не отправилось
                    }
                }

                Log::info('Product rejected by admin', [
                    'product_id' => $product->id,
                    'admin_id' => auth()->id(),
                    'supplier_id' => $product->supplier_id,
                    'comment' => $validated['moderation_comment'],
                ]);
            });

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
