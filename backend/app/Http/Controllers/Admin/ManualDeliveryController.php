<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\ManualDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ManualDeliveryController extends Controller
{
    protected $manualDeliveryService;

    public function __construct(ManualDeliveryService $manualDeliveryService)
    {
        $this->manualDeliveryService = $manualDeliveryService;
    }

    /**
     * Отображение списка заказов, ожидающих ручной обработки
     */
    public function index(Request $request)
    {
        $deliveryType = $request->query('delivery_type', 'manual');
        $statusFilter = $request->query('status', 'all'); // Фильтр по статусу: all, processing, completed
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $customerEmail = $request->query('customer_email');
        $customerId = $request->query('customer_id');
        $orderNumber = $request->query('order_number');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc'); // По умолчанию новые первые
        
        // Получаем заказы с фильтрацией по типу выдачи
        // Показываем как заказы в обработке, так и обработанные заказы с ручной выдачей
        $query = Purchase::with(['user:id,name,email', 'serviceAccount:id,title,title_en,title_uk,delivery_type', 'transaction:id,currency,payment_method,amount']);
        
        // Фильтр по статусу
        if ($statusFilter === 'processing') {
            $query->where('status', Purchase::STATUS_PROCESSING);
        } elseif ($statusFilter === 'completed') {
            $query->where('status', Purchase::STATUS_COMPLETED);
        } else {
            // all - показываем и processing, и completed
            $query->whereIn('status', [Purchase::STATUS_PROCESSING, Purchase::STATUS_COMPLETED]);
        }
        
        // Фильтруем по типу выдачи - только заказы с ручной выдачей
        $query->whereHas('serviceAccount', function($q) use ($deliveryType) {
            if ($deliveryType !== 'all') {
                $q->where('delivery_type', $deliveryType);
            } else {
                // Если all, показываем все типы, но только те, что были с ручной выдачей
                $q->whereIn('delivery_type', ['manual']);
            }
        });
        
        // Фильтр по дате создания "с"
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        // Фильтр по дате создания "по"
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        // Фильтр по email покупателя
        if ($customerEmail) {
            $query->where(function($q) use ($customerEmail) {
                $q->whereHas('user', function($userQuery) use ($customerEmail) {
                    $userQuery->where('email', 'like', "%{$customerEmail}%");
                })->orWhere('guest_email', 'like', "%{$customerEmail}%");
            });
        }
        
        // Фильтр по ID покупателя
        if ($customerId) {
            $query->where('user_id', $customerId);
        }
        
        // Поиск по номеру заказа
        if ($orderNumber) {
            $query->where('order_number', 'like', "%{$orderNumber}%");
        }
        
        // Сортировка
        $allowedSortFields = ['created_at', 'total_amount', 'quantity'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'asc';
        
        // Для сортировки по времени в обработке используем вычисляемое поле
        if ($sortBy === 'processing_time') {
            $query->selectRaw('purchases.*, TIMESTAMPDIFF(HOUR, purchases.created_at, NOW()) as processing_time_hours');
            $sortBy = 'processing_time_hours';
        }
        
        $pendingOrders = $query->orderBy($sortBy, $sortOrder)->get();
        $statistics = $this->manualDeliveryService->getStatistics();

        return view('admin.manual-delivery.index', compact(
            'pendingOrders', 
            'statistics', 
            'deliveryType',
            'statusFilter',
            'dateFrom',
            'dateTo',
            'customerEmail',
            'customerId',
            'orderNumber',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Отображение детальной информации о заказе для обработки
     */
    public function show(Purchase $purchase)
    {
        // Проверяем, что заказ требует ручной обработки
        if (!$purchase->requiresManualProcessing()) {
            return redirect()->route('admin.manual-delivery.index')
                ->with('error', 'Этот заказ не требует ручной обработки.');
        }

        $purchase->load(['user', 'serviceAccount', 'transaction', 'processor']);

        return view('admin.manual-delivery.show', compact('purchase'));
    }

    /**
     * Обработка заказа (выдача товара вручную)
     */
    public function process(Request $request, Purchase $purchase)
    {
        // Валидация
        $validated = $request->validate([
            'account_data' => 'required|array|min:1',
            'account_data.*' => 'required|string|min:1',
            'processing_notes' => 'nullable|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        // Проверяем количество аккаунтов
        if (count($validated['account_data']) !== $purchase->quantity) {
            throw ValidationException::withMessages([
                'account_data' => "Количество аккаунтов должно быть равно количеству товара ({$purchase->quantity})."
            ]);
        }

        // КРИТИЧНО: Санитизация данных аккаунтов от XSS
        // Удаляем только HTML/JS теги, сохраняя специальные символы (они могут быть частью данных аккаунта)
        $sanitizedAccountData = array_map(function($account) {
            // Удаляем HTML/JS теги, но сохраняем специальные символы
            $sanitized = strip_tags($account);
            // Удаляем потенциально опасные JavaScript события (onclick, onerror и т.д.)
            $sanitized = preg_replace('/on\w+="[^"]*"/i', '', $sanitized);
            $sanitized = preg_replace("/on\w+='[^']*'/i", '', $sanitized);
            return trim($sanitized);
        }, $validated['account_data']);

        try {
            // Обрабатываем заказ с санитизированными данными
            $this->manualDeliveryService->processPurchase(
                $purchase,
                auth()->user(),
                $sanitizedAccountData,
                $validated['processing_notes'] ?? null
            );

            // Сохраняем внутренние заметки администратора отдельно
            if (!empty($validated['admin_notes'])) {
                $purchase->update(['admin_notes' => $validated['admin_notes']]);
            }

            return redirect()->route('admin.manual-delivery.index')
                ->with('success', "Заказ #{$purchase->order_number} успешно обработан.");

        } catch (\Exception $e) {
            Log::error('Manual delivery processing failed', [
                'purchase_id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Ошибка при обработке заказа: ' . $e->getMessage());
        }
    }

    /**
     * Получить статистику (API endpoint для AJAX)
     */
    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => $this->manualDeliveryService->getStatistics(),
        ]);
    }

    /**
     * Получить количество заказов, ожидающих обработки (для badge в меню)
     */
    public function getPendingCount()
    {
        $count = Purchase::where('status', Purchase::STATUS_PROCESSING)
            ->whereHas('serviceAccount', function($q) {
                $q->where('delivery_type', 'manual');
            })
            ->count();
        
        return response()->json(['count' => $count]);
    }

    /**
     * Расширенная аналитика по ручной обработке заказов
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->query('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->query('date_to', now()->format('Y-m-d'));
        
        // Общая статистика
        $totalOrders = Purchase::where('status', Purchase::STATUS_PROCESSING)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        $completedOrders = Purchase::where('status', Purchase::STATUS_COMPLETED)
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->count();
        
        // Статистика по времени обработки
        $processingTimes = Purchase::where('status', Purchase::STATUS_COMPLETED)
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->get()
            ->map(function ($purchase) {
                return $purchase->created_at->diffInHours($purchase->processed_at);
            });
        
        $avgProcessingTime = $processingTimes->isNotEmpty() ? $processingTimes->avg() : 0;
        $minProcessingTime = $processingTimes->isNotEmpty() ? $processingTimes->min() : 0;
        $maxProcessingTime = $processingTimes->isNotEmpty() ? $processingTimes->max() : 0;
        
        // Статистика по дням недели
        $ordersByDay = Purchase::where('status', Purchase::STATUS_COMPLETED)
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->selectRaw('DAYNAME(processed_at) as day_name, COUNT(*) as count')
            ->groupBy('day_name')
            ->get()
            ->sortBy(function($item) {
                $days = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                return $days[$item->day_name] ?? 8;
            })
            ->values();
        
        // Статистика по менеджерам
        $ordersByManager = Purchase::where('status', Purchase::STATUS_COMPLETED)
            ->whereNotNull('processed_by')
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->with('processor:id,name,email')
            ->get()
            ->groupBy('processed_by')
            ->map(function ($orders, $managerId) {
                $manager = $orders->first()->processor;
                return [
                    'manager_id' => $managerId,
                    'manager_name' => $manager ? $manager->name : 'Unknown',
                    'manager_email' => $manager ? $manager->email : 'N/A',
                    'orders_count' => $orders->count(),
                    'avg_processing_time' => $orders->avg(function ($order) {
                        return $order->created_at->diffInHours($order->processed_at);
                    }),
                ];
            })
            ->values();
        
        // Статистика по типам выдачи
        $ordersByDeliveryType = Purchase::where('status', Purchase::STATUS_COMPLETED)
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->whereHas('serviceAccount')
            ->with('serviceAccount:id,delivery_type')
            ->get()
            ->groupBy(function ($purchase) {
                return $purchase->serviceAccount->delivery_type ?? 'unknown';
            })
            ->map(function ($orders) {
                return [
                    'delivery_type' => $orders->first()->serviceAccount->delivery_type ?? 'unknown',
                    'orders_count' => $orders->count(),
                    'avg_processing_time' => $orders->avg(function ($order) {
                        return $order->created_at->diffInHours($order->processed_at);
                    }),
                ];
            })
            ->values();
        
        // Заказы в ожидании товара
        $waitingStockCount = Purchase::where('is_waiting_stock', true)
            ->where('status', Purchase::STATUS_PROCESSING)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        // Заказы с ошибками обработки
        $errorOrdersCount = Purchase::whereNotNull('processing_error')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        return view('admin.manual-delivery.analytics', compact(
            'totalOrders',
            'completedOrders',
            'avgProcessingTime',
            'minProcessingTime',
            'maxProcessingTime',
            'ordersByDay',
            'ordersByManager',
            'ordersByDeliveryType',
            'waitingStockCount',
            'errorOrdersCount',
            'dateFrom',
            'dateTo'
        ));
    }
}
