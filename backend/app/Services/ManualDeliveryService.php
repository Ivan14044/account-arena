<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\ServiceAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotifierService;
use App\Services\EmailService;
use App\Services\NotificationTemplateService;

/**
 * Сервис для обработки ручной выдачи товаров
 */
class ManualDeliveryService
{
    /**
     * Обработать заказ с ручной выдачей
     * 
     * @param Purchase $purchase Заказ
     * @param User $admin Администратор, обрабатывающий заказ
     * @param array $accountData Данные аккаунтов для выдачи
     * @param string|null $notes Заметки менеджера
     * @return Purchase
     * @throws \Exception
     */
    public function processPurchase(
        Purchase $purchase,
        User $admin,
        array $accountData,
        ?string $notes = null
    ): Purchase {
        // Валидация статуса
        if ($purchase->status !== Purchase::STATUS_PROCESSING) {
            throw new \Exception('Purchase is not in processing status. Current status: ' . $purchase->status);
        }

        // Валидация типа выдачи
        if (!$purchase->serviceAccount || !$purchase->serviceAccount->requiresManualDelivery()) {
            throw new \Exception('This purchase does not require manual delivery');
        }

        // Валидация количества аккаунтов
        if (count($accountData) !== $purchase->quantity) {
            throw new \Exception(
                "Account count mismatch. Expected: {$purchase->quantity}, provided: " . count($accountData)
            );
        }

        // Валидация данных аккаунтов
        foreach ($accountData as $index => $account) {
            if (empty(trim($account))) {
                throw new \Exception("Account data at index {$index} is empty");
            }
        }

        return DB::transaction(function () use ($purchase, $admin, $accountData, $notes) {
            // Обновляем заказ
            $purchase->update([
                'status' => Purchase::STATUS_COMPLETED,
                'account_data' => $accountData,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'processing_notes' => $notes,
            ]);

            Log::info('Manual delivery processed', [
                'purchase_id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'quantity' => $purchase->quantity,
            ]);

            // Отправляем уведомление пользователю
            $this->notifyUserAboutDelivery($purchase);

            return $purchase->fresh();
        });
    }

    /**
     * Получить список заказов, ожидающих ручной обработки
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingManualOrders()
    {
        return Purchase::with([
            'user' => function($q) {
                $q->select('id', 'name', 'email');
            },
            'serviceAccount' => function($q) {
                $q->select('id', 'title', 'title_en', 'title_uk', 'manual_delivery_instructions');
            },
            'transaction' => function($q) {
                $q->select('id', 'currency', 'payment_method');
            }
        ])
        ->pendingManualProcessing()
        ->orderBy('created_at', 'asc')
        ->get();
    }

    /**
     * Получить статистику по ручной обработке
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'pending' => Purchase::pendingManualProcessing()->count(),
            'processed_today' => Purchase::where('status', Purchase::STATUS_COMPLETED)
                ->whereNotNull('processed_by')
                ->whereDate('processed_at', today())
                ->count(),
            'processed_this_week' => Purchase::where('status', Purchase::STATUS_COMPLETED)
                ->whereNotNull('processed_by')
                ->whereBetween('processed_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'average_processing_time' => $this->getAverageProcessingTime(),
        ];
    }

    /**
     * Получить среднее время обработки заказа
     * 
     * @return float|null Время в часах
     */
    private function getAverageProcessingTime(): ?float
    {
        $processed = Purchase::where('status', Purchase::STATUS_COMPLETED)
            ->whereNotNull('processed_by')
            ->whereNotNull('processed_at')
            ->whereNotNull('created_at')
            ->get();

        if ($processed->isEmpty()) {
            return null;
        }

        $totalHours = $processed->sum(function ($purchase) {
            return $purchase->created_at->diffInHours($purchase->processed_at);
        });

        return round($totalHours / $processed->count(), 2);
    }

    /**
     * Уведомить пользователя о выдаче товара
     * 
     * @param Purchase $purchase
     * @return void
     */
    private function notifyUserAboutDelivery(Purchase $purchase): void
    {
        try {
            // Уведомление через систему уведомлений
            if ($purchase->user_id) {
                $notificationService = app(NotificationTemplateService::class);
                $notificationService->sendToUser($purchase->user, 'manual_delivery_completed', [
                    'order_number' => $purchase->order_number,
                    'product_title' => $purchase->serviceAccount->title ?? 'Product',
                ]);
            }

            // Email уведомление
            $userEmail = $purchase->user_id 
                ? $purchase->user->email 
                : $purchase->guest_email;

            if ($userEmail) {
                $emailParams = [
                    'order_number' => $purchase->order_number,
                    'product_title' => $purchase->serviceAccount->title ?? 'Product',
                ];

                if ($purchase->user_id) {
                    EmailService::send('manual_delivery_completed', $purchase->user_id, $emailParams);
                } else {
                    // Для гостевых покупок отправляем на guest_email
                    // (требуется реализация метода sendToGuest в EmailService)
                    Log::info('Manual delivery completed for guest', [
                        'order_number' => $purchase->order_number,
                        'guest_email' => $userEmail,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Не ломаем процесс обработки из-за ошибки уведомления
            Log::error('Failed to notify user about manual delivery', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить администратора о новом заказе на ручную обработку
     * 
     * @param Purchase $purchase
     * @return void
     */
    public function notifyAdminAboutNewOrder(Purchase $purchase): void
    {
        try {
            NotifierService::sendFromTemplate(
                'manual_delivery_new_order',
                'admin_manual_delivery_new_order',
                [
                    'order_number' => $purchase->order_number,
                    'product_title' => $purchase->serviceAccount->title ?? 'Product',
                    'user_email' => $purchase->user_id 
                        ? $purchase->user->email 
                        : $purchase->guest_email,
                    'quantity' => $purchase->quantity,
                    'total_amount' => number_format($purchase->total_amount, 2),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify admin about new manual order', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
