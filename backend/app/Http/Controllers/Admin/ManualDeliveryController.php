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
        $pendingOrders = $this->manualDeliveryService->getPendingManualOrders();
        $statistics = $this->manualDeliveryService->getStatistics();

        return view('admin.manual-delivery.index', compact('pendingOrders', 'statistics'));
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

        try {
            // Обрабатываем заказ
            $this->manualDeliveryService->processPurchase(
                $purchase,
                auth()->user(),
                $validated['account_data'],
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
}
