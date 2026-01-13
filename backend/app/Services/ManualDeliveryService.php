<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseStatusHistory;
use App\Models\ServiceAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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

        try {
            $result = DB::transaction(function () use ($purchase, $admin, $accountData, $notes) {
                // КРИТИЧНО: Блокируем заказ для обновления (защита от race condition)
                $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
                
                // Сбрасываем предыдущую ошибку при новой попытке обработки
                if ($purchase->processing_error) {
                    $purchase->update(['processing_error' => null]);
                }
                
                // Проверяем статус после блокировки
                if ($purchase->status !== Purchase::STATUS_PROCESSING) {
                    throw new \Exception('Purchase is not in processing status. Current status: ' . $purchase->status);
                }

            // КРИТИЧНО: Проверяем наличие товара перед обработкой
            $product = ServiceAccount::lockForUpdate()->findOrFail($purchase->service_account_id);
            $availableStock = $product->getAvailableStock();
            
            if ($availableStock < $purchase->quantity) {
                // Переводим заказ в статус ожидания товара вместо исключения
                $purchase->update([
                    'is_waiting_stock' => true,
                ]);
                
                // Инвалидируем кеш счетчика необработанных заказов
                Cache::forget('manual_delivery_pending_count');
                
                // Записываем историю изменения статуса
                PurchaseStatusHistory::createHistory(
                    $purchase,
                    Purchase::STATUS_PROCESSING,
                    $purchase->status,
                    $admin,
                    'Товар отсутствует, заказ переведен в ожидание',
                    [
                        'available_stock' => $availableStock,
                        'required_quantity' => $purchase->quantity,
                        'is_waiting_stock' => true,
                    ]
                );
                
                return $purchase->fresh();
            }
            
            // Если товар есть, сбрасываем флаг ожидания (если был установлен ранее)
            if ($purchase->is_waiting_stock) {
                $purchase->update([
                    'is_waiting_stock' => false,
                ]);
                
                // Инвалидируем кеш счетчика необработанных заказов
                Cache::forget('manual_delivery_pending_count');
            }

            // КРИТИЧНО: Увеличиваем счетчик used при обработке заказа
            $currentUsed = $product->used ?? 0;
            $product->used = $currentUsed + $purchase->quantity;
            $product->save();

            // Сохраняем старый статус для истории
            $oldStatus = $purchase->status;

            // Обновляем заказ
            $purchase->update([
                'status' => Purchase::STATUS_COMPLETED,
                'account_data' => $accountData,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'processing_notes' => $notes,
                'is_waiting_stock' => false, // Сбрасываем флаг ожидания при успешной обработке
            ]);
            
            // Инвалидируем кеш счетчика необработанных заказов, чтобы счетчик обновился сразу
            Cache::forget('manual_delivery_pending_count');

            // Записываем историю изменения статуса
            PurchaseStatusHistory::createHistory(
                $purchase,
                Purchase::STATUS_COMPLETED,
                $oldStatus,
                $admin,
                'Заказ обработан менеджером',
                [
                    'account_data_count' => count($accountData),
                    'quantity' => $purchase->quantity,
                    'processing_notes' => $notes,
                ]
            );

            // Улучшенное логирование с audit trail
            $processingTime = $purchase->created_at->diffInHours(now());
            Log::info('Manual delivery order processed', [
                'purchase_id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'quantity' => $purchase->quantity,
                'old_status' => Purchase::STATUS_PROCESSING,
                'new_status' => Purchase::STATUS_COMPLETED,
                'processing_time_hours' => $processingTime,
                'product_id' => $product->id,
                'product_used_before' => $currentUsed,
                'product_used_after' => $product->used,
                'available_stock_before' => $availableStock,
            ]);

            return $purchase->fresh();
            });

            // ВАЖНО: Уведомления вынесены за пределы транзакции
            if ($result->status === Purchase::STATUS_COMPLETED) {
                $this->notifyUserAboutDelivery($result);
            } elseif ($result->is_waiting_stock) {
                $this->notifyUserAboutOutOfStock($result);
                $this->notifyAdminAboutOutOfStock($result);
            }

            return $result;
        } catch (\Throwable $e) {
            // Записываем ошибку в processing_error
            $purchase->update([
                'processing_error' => $e->getMessage(),
            ]);
            
            // Уведомляем пользователя об ошибке
            $this->notifyUserAboutProcessingError($purchase, $e->getMessage());
            
            // Логируем ошибку
            Log::error('Manual delivery processing error', [
                'purchase_id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'admin_id' => $admin->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Пробрасываем исключение дальше
            throw $e;
        }
    }

    /**
     * Отменить заказ в статусе processing
     * 
     * @param Purchase $purchase Заказ
     * @param User $user Пользователь, отменяющий заказ
     * @return Purchase
     * @throws \Exception
     */
    public function cancelProcessingOrder(Purchase $purchase, $user, ?string $cancellationReason = null): Purchase
    {
        // Валидация статуса
        if ($purchase->status !== Purchase::STATUS_PROCESSING) {
            throw new \Exception('Order cannot be cancelled. Current status: ' . $purchase->status);
        }

        // Проверяем, что пользователь отменяет свой заказ
        if ($purchase->user_id) {
            // Для авторизованных пользователей проверяем user_id
            if (!$user->id || $purchase->user_id !== $user->id) {
                throw new \Exception('You can only cancel your own orders');
            }
        } else {
            // Для гостевых заказов проверяем email
            // Для гостей email может быть в свойстве email или guest_email
            $userEmail = $user->email ?? (property_exists($user, 'guest_email') ? $user->guest_email : null);
            $purchaseEmail = strtolower(trim($purchase->guest_email ?? ''));
            if (!$userEmail || $purchaseEmail !== strtolower(trim($userEmail))) {
                throw new \Exception('You can only cancel your own orders');
            }
        }

        return DB::transaction(function () use ($purchase, $user, $cancellationReason) {
            // Сохраняем старый статус для истории
            $oldStatus = $purchase->status;

            // Обновляем статус заказа
            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
            ]);
            
            // Инвалидируем кеш счетчика необработанных заказов, чтобы счетчик обновился сразу
            Cache::forget('manual_delivery_pending_count');

            // Записываем историю изменения статуса
            PurchaseStatusHistory::createHistory(
                $purchase,
                Purchase::STATUS_CANCELLED,
                $oldStatus,
                $user->id ? $user : null,
                $cancellationReason ?? 'Заказ отменен пользователем',
                [
                    'cancellation_reason' => $cancellationReason,
                    'cancelled_by_user' => true,
                ]
            );

            // Логируем отмену
            $userEmail = $user->email ?? $purchase->guest_email;
            Log::info('Processing order cancelled', [
                'purchase_id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'user_id' => $user->id,
                'user_email' => $userEmail,
                'old_status' => Purchase::STATUS_PROCESSING,
                'new_status' => Purchase::STATUS_CANCELLED,
            ]);

            // Уведомляем администратора об отмене заказа
            try {
                $purchase->load('serviceAccount');
                $productTitle = $purchase->serviceAccount->title ?? 'Product';
                
                \App\Services\NotifierService::send(
                    'manual_delivery_cancelled',
                    "Заказ #{$purchase->order_number} отменен пользователем",
                    "Пользователь {$userEmail} отменил заказ #{$purchase->order_number} на товар \"{$productTitle}\"",
                    'warning'
                );
            } catch (\Throwable $e) {
                // Не ломаем процесс отмены из-за ошибки уведомления
                Log::error('Failed to notify admin about order cancellation', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // TODO: Возврат средств через Transaction (если требуется)
            // Это зависит от бизнес-логики проекта

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
        // Оптимизированный запрос с явным указанием полей для избежания N+1
        return Purchase::with([
            'user:id,name,email',
            'serviceAccount:id,title,title_en,title_uk,manual_delivery_instructions',
            'transaction:id,currency,payment_method,amount'
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
    public function getAverageProcessingTime(): ?float
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
                    EmailService::sendToGuest($userEmail, 'manual_delivery_completed', $emailParams);
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
     * Уведомить пользователя об отсутствии товара при обработке заказа
     * 
     * @param Purchase $purchase
     * @return void
     */
    private function notifyUserAboutOutOfStock(Purchase $purchase): void
    {
        try {
            // Уведомление через систему уведомлений
            if ($purchase->user_id) {
                $notificationService = app(NotificationTemplateService::class);
                $notificationService->sendToUser($purchase->user, 'manual_delivery_out_of_stock', [
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
                    EmailService::send('manual_delivery_out_of_stock', $purchase->user_id, $emailParams);
                } else {
                    // Для гостевых покупок отправляем на guest_email
                    EmailService::sendToGuest($userEmail, 'manual_delivery_out_of_stock', $emailParams);
                }
            }
        } catch (\Throwable $e) {
            // Не ломаем процесс из-за ошибки уведомления
            Log::error('Failed to notify user about out of stock', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить администратора об отсутствии товара при обработке заказа
     * 
     * @param Purchase $purchase
     * @return void
     */
    private function notifyAdminAboutOutOfStock(Purchase $purchase): void
    {
        try {
            $purchase->load('serviceAccount');
            $productTitle = $purchase->serviceAccount->title ?? 'Product';
            $availableStock = $purchase->serviceAccount->getAvailableStock();
            
            NotifierService::send(
                'manual_delivery_out_of_stock',
                "Отсутствие товара для заказа #{$purchase->order_number}",
                "При обработке заказа #{$purchase->order_number} на товар \"{$productTitle}\" обнаружено отсутствие товара. Доступно: {$availableStock}, требуется: {$purchase->quantity}. Требуется решение менеджера.",
                'error'
            );
        } catch (\Throwable $e) {
            // Не ломаем процесс из-за ошибки уведомления
            Log::error('Failed to notify admin about out of stock', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить пользователя о создании заказа в статусе processing
     * 
     * @param Purchase $purchase
     * @return void
     */
    public function notifyUserAboutOrderCreated(Purchase $purchase): void
    {
        try {
            // Уведомление через систему уведомлений
            if ($purchase->user_id) {
                $notificationService = app(NotificationTemplateService::class);
                $notificationService->sendToUser($purchase->user, 'manual_delivery_order_created', [
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
                    EmailService::send('manual_delivery_order_created', $purchase->user_id, $emailParams);
                } else {
                    // Для гостевых покупок отправляем на guest_email
                    EmailService::sendToGuest($userEmail, 'manual_delivery_order_created', $emailParams);
                }
            }
        } catch (\Throwable $e) {
            // Не ломаем процесс создания заказа из-за ошибки уведомления
            Log::error('Failed to notify user about manual order creation', [
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
            
            // Примечание: Кеш уже инвалидирован в ProductPurchaseService::createProductPurchase
            // перед вызовом этого метода, поэтому здесь инвалидация не требуется
            // Но оставляем для надежности на случай прямого вызова метода
            Cache::forget('manual_delivery_pending_count');
        } catch (\Throwable $e) {
            // Кеш уже инвалидирован в ProductPurchaseService, поэтому счетчик обновится даже при ошибке
            Log::error('Failed to notify admin about new manual order', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить пользователя об ошибке обработки заказа
     * 
     * @param Purchase $purchase
     * @param string $errorMessage
     * @return void
     */
    private function notifyUserAboutProcessingError(Purchase $purchase, string $errorMessage): void
    {
        try {
            // Уведомление через систему уведомлений
            if ($purchase->user_id) {
                $notificationService = app(NotificationTemplateService::class);
                $notificationService->sendToUser($purchase->user, 'manual_delivery_processing_error', [
                    'order_number' => $purchase->order_number,
                    'product_title' => $purchase->serviceAccount->title ?? 'Product',
                    'error_message' => $errorMessage,
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
                    'error_message' => $errorMessage,
                ];

                if ($purchase->user_id) {
                    EmailService::send('manual_delivery_processing_error', $purchase->user_id, $emailParams);
                } else {
                    // Для гостевых покупок отправляем на guest_email
                    EmailService::sendToGuest($userEmail, 'manual_delivery_processing_error', $emailParams);
                }
            }
        } catch (\Throwable $e) {
            // Не ломаем процесс из-за ошибки уведомления
            Log::error('Failed to notify user about processing error', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
