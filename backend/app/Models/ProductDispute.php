<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierEarning;

class ProductDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'supplier_id',
        'service_account_id',
        'reason',
        'customer_description',
        'screenshot_url',
        'screenshot_type',
        'admin_decision',
        'admin_comment',
        'refund_amount',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    // Константы для статусов
    const STATUS_NEW = 'new';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';

    // Константы для причин
    const REASON_INVALID_ACCOUNT = 'invalid_account';
    const REASON_WRONG_DATA = 'wrong_data';
    const REASON_NOT_WORKING = 'not_working';
    const REASON_ALREADY_USED = 'already_used';
    const REASON_BANNED = 'banned';
    const REASON_OTHER = 'other';

    // Константы для решений
    const DECISION_REFUND = 'refund';
    const DECISION_REPLACEMENT = 'replacement';
    const DECISION_REJECTED = 'rejected';

    /**
     * Связь с транзакцией
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Связь с пользователем (покупателем)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с поставщиком (или администратором)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id')->withDefault([
            'name' => 'Администратор',
            'email' => 'admin',
            'is_supplier' => false,
        ]);
    }

    /**
     * Проверить, является ли претензия на товар администратора
     */
    public function isAdminProduct(): bool
    {
        return $this->supplier_id === null;
    }

    /**
     * Связь с товаром
     */
    public function serviceAccount(): BelongsTo
    {
        return $this->belongsTo(ServiceAccount::class);
    }

    /**
     * Связь с администратором, который обработал претензию
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope для новых претензий
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope для претензий на рассмотрении
     */
    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    /**
     * Scope для решенных претензий
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope для отклоненных претензий
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope для претензий конкретного поставщика
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Обработать претензию с возвратом средств
     */
    public function resolveWithRefund($adminId, $comment = null)
    {
        DB::transaction(function () use ($adminId, $comment) {
            // ВАЖНО: Блокируем саму запись претензии для предотвращения двойного вызова
            $dispute = self::where('id', $this->id)->lockForUpdate()->firstOrFail();

            // ВАЖНО: Проверяем статус ВНУТРИ транзакции после блокировки
            if ($dispute->status === self::STATUS_RESOLVED || $dispute->status === self::STATUS_REJECTED) {
                throw new \Exception('Dispute already resolved or rejected');
            }

            // ВАЖНО: Проверяем, что транзакция еще не была возвращена (также после блокировки)
            if ($dispute->transaction->status === 'refunded') {
                throw new \Exception('Transaction already refunded');
            }

            // ВАЖНО: Возвращаем деньги пользователю на баланс через BalanceService
            $balanceService = app(\App\Services\BalanceService::class);
            try {
                $balanceService->topUp(
                    $dispute->user,
                    $dispute->refund_amount,
                    \App\Services\BalanceService::TYPE_REFUND,
                    [
                        'dispute_id' => $dispute->id,
                        'transaction_id' => $dispute->transaction_id,
                        'admin_id' => $adminId,
                        'comment' => $comment,
                    ]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('ProductDispute refund: Failed to refund via BalanceService', [
                    'dispute_id' => $dispute->id,
                    'user_id' => $dispute->user->id,
                    'refund_amount' => $dispute->refund_amount,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            // ВАЖНО: Обрабатываем SupplierEarning только если товар от поставщика
            if ($dispute->supplier_id && $dispute->supplier) {
                // Ищем SupplierEarning для этой транзакции
                $supplierEarning = SupplierEarning::where('transaction_id', $dispute->transaction_id)
                    ->where('supplier_id', $dispute->supplier_id)
                    ->where('status', '!=', 'reversed')
                    ->first();

                if ($supplierEarning) {
                    // Списываем только ту сумму, которая была зачислена поставщику (с учетом комиссии)
                    $supplierAmountToDeduct = $supplierEarning->amount;
                    $originalStatus = $supplierEarning->status;

                    if ($originalStatus === 'withdrawn' || $originalStatus === 'available') {
                        // Если средства уже доступны или выведены, списываем с баланса
                        if ($dispute->supplier->supplier_balance < $supplierAmountToDeduct) {
                            throw new \Exception("Insufficient supplier balance for refund. Required: {$supplierAmountToDeduct} USD");
                        }
                        $dispute->supplier->decrement('supplier_balance', $supplierAmountToDeduct);
                    }
                    
                    $supplierEarning->reverse('Product dispute refund');
                } else {
                    // КРИТИЧНО: Если SupplierEarning не найден, мы НЕ можем списывать полную сумму покупки (refund_amount),
                    // так как она включает комиссию сервиса. Списываем 0 или бросаем ошибку для ручного разбора.
                    \Illuminate\Support\Facades\Log::error('ProductDispute refund: SupplierEarning not found for supplier product', [
                        'dispute_id' => $dispute->id,
                        'transaction_id' => $dispute->transaction_id,
                    ]);
                    throw new \Exception("Supplier earning record not found. Please contact developer to resolve manually.");
                }
            }

            // Обновляем статус транзакции
            $dispute->transaction->update(['status' => 'refunded']);

            // Обновляем претензию
            $dispute->update([
                'status' => self::STATUS_RESOLVED,
                'admin_decision' => self::DECISION_REFUND,
                'admin_comment' => $comment,
                'resolved_at' => now(),
                'resolved_by' => $adminId,
            ]);

            // Уведомления
            if ($dispute->supplier_id && $dispute->supplier) {
                $dispute->notifySupplier();
                $dispute->supplier->calculateSupplierRating();
            }
            $dispute->notifyCustomer();
        });
    }

    /**
     * Обработать претензию с заменой товара
     */
    public function resolveWithReplacement($adminId, $comment = null)
    {
        DB::transaction(function () use ($adminId, $comment) {
            // Обновляем претензию
            $this->update([
                'status' => self::STATUS_RESOLVED,
                'admin_decision' => self::DECISION_REPLACEMENT,
                'admin_comment' => $comment,
                'resolved_at' => now(),
                'resolved_by' => $adminId,
            ]);

            // Отправляем уведомление поставщику (только если товар от поставщика)
            if ($this->supplier_id && $this->supplier) {
                $this->notifySupplier();
                
                // Пересчитываем рейтинг поставщика
                $this->supplier->calculateSupplierRating();
            }

            // Отправляем уведомление покупателю
            $this->notifyCustomer();
        });
    }

    /**
     * Отклонить претензию
     */
    public function reject($adminId, $comment)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'admin_decision' => self::DECISION_REJECTED,
            'admin_comment' => $comment,
            'resolved_at' => now(),
            'resolved_by' => $adminId,
        ]);

        // Отправляем уведомление покупателю
        $this->notifyCustomer();

        // Пересчитываем рейтинг поставщика (только если товар от поставщика)
        if ($this->supplier_id && $this->supplier) {
            $this->supplier->calculateSupplierRating();
        }
    }

    /**
     * Отправить уведомление поставщику
     */
    protected function notifySupplier()
    {
        SupplierNotification::create([
            'user_id' => $this->supplier_id,
            'type' => 'product_dispute',
            'title' => 'Претензия на товар',
            'message' => "Претензия #{$this->id} на товар \"{$this->serviceAccount->title}\" решена. Решение: " . $this->getDecisionText(),
            'data' => [
                'dispute_id' => $this->id,
                'decision' => $this->admin_decision,
            ],
        ]);
    }

    /**
     * Отправить уведомление покупателю
     */
    protected function notifyCustomer()
    {
        $user = $this->user;
        if (!$user) {
            return;
        }

        $decisionText = $this->getDecisionText($user->lang ?? 'ru');
        $comment = $this->admin_comment ?? '';

        $notificationService = app(\App\Services\NotificationTemplateService::class);
        $notificationService->sendToUser($user, 'dispute_resolved', [
            'dispute_id' => (string)$this->id,
            'decision' => $decisionText,
            'comment' => $comment,
        ]);
    }

    /**
     * Получить текст решения
     */
    public function getDecisionText(): string
    {
        return match($this->admin_decision) {
            self::DECISION_REFUND => __('Возврат средств'),
            self::DECISION_REPLACEMENT => __('Замена товара'),
            self::DECISION_REJECTED => __('Отклонено'),
            default => __('Не обработано'),
        };
    }

    /**
     * Получить текст причины
     */
    public function getReasonText(): string
    {
        return match($this->reason) {
            self::REASON_INVALID_ACCOUNT => __('Невалидный аккаунт'),
            self::REASON_WRONG_DATA => __('Неверные данные'),
            self::REASON_NOT_WORKING => __('Не работает'),
            self::REASON_ALREADY_USED => __('Уже использован'),
            self::REASON_BANNED => __('Заблокирован'),
            self::REASON_OTHER => __('Другое'),
            default => __('Не указано'),
        };
    }

    /**
     * Получить класс badge для статуса
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_NEW => 'badge-warning',
            self::STATUS_IN_REVIEW => 'badge-info',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Получить текст статуса
     */
    public function getStatusText(): string
    {
        return match($this->status) {
            self::STATUS_NEW => __('Новая'),
            self::STATUS_IN_REVIEW => __('На рассмотрении'),
            self::STATUS_RESOLVED => __('Решена'),
            self::STATUS_REJECTED => __('Отклонена'),
            default => __('Неизвестно'),
        };
    }
}
