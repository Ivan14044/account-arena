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
        $query->whereHas('serviceAccount', function($q) {
            $q->where('delivery_type', 'manual');
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
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';
        
        // Сортировка по времени в обработке
        if ($sortBy === 'processing_time') {
            $query->selectRaw('purchases.*, TIMESTAMPDIFF(HOUR, purchases.created_at, NOW()) as processing_time_hours');
            $sortBy = 'processing_time_hours';
        }
        
        $pendingOrders = $query->orderBy($sortBy, $sortOrder)->paginate(50)->withQueryString(); // Добавлена пагинация
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

        // КРИТИЧНО: Убрали strip_tags, чтобы не повредить пароли/конфиги. 
        $sanitizedAccountData = array_map(function($account) {
            return trim($account);
        }, $validated['account_data']);

        try {
            // ВАЖНО: Используем транзакцию и блокировку заказа
            \Illuminate\Support\Facades\DB::transaction(function () use ($purchase, $sanitizedAccountData, $validated) {
                // Блокируем заказ для предотвращения двойной обработки
                $lockedPurchase = Purchase::where('id', $purchase->id)->lockForUpdate()->firstOrFail();
                
                if ($lockedPurchase->status !== Purchase::STATUS_PROCESSING) {
                    throw new \Exception("Заказ уже был обработан.");
                }

                // Обрабатываем заказ
                $this->manualDeliveryService->processPurchase(
                    $lockedPurchase,
                    auth()->user(),
                    $sanitizedAccountData,
                    $validated['processing_notes'] ?? null
                );

                // Сохраняем внутренние заметки администратора отдельно
                if (!empty($validated['admin_notes'])) {
                    $lockedPurchase->update(['admin_notes' => $validated['admin_notes']]);
                }
            });

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
        
        Log::debug('[Manual Delivery Badge] Counting pending orders', [
            'count' => $count,
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ]);
        
        return response()->json(['count' => $count]);
    }

}
