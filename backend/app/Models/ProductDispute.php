<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
            // Возвращаем деньги пользователю на баланс
            $this->user->increment('balance', $this->refund_amount);

            // Списываем с баланса поставщика (только если товар от поставщика!)
            if ($this->supplier_id && $this->supplier) {
                $this->supplier->decrement('supplier_balance', $this->refund_amount);
            }

            // Обновляем статус транзакции
            $this->transaction->update(['status' => 'refunded']);

            // Обновляем претензию
            $this->update([
                'status' => self::STATUS_RESOLVED,
                'admin_decision' => self::DECISION_REFUND,
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
        Notification::create([
            'user_id' => $this->user_id,
            'type' => 'dispute_resolved',
            'title' => 'Претензия рассмотрена',
            'message' => "Ваша претензия #{$this->id} рассмотрена. Решение: " . $this->getDecisionText(),
            'data' => [
                'dispute_id' => $this->id,
                'decision' => $this->admin_decision,
            ],
        ]);
    }

    /**
     * Получить текст решения
     */
    public function getDecisionText(): string
    {
        return match($this->admin_decision) {
            self::DECISION_REFUND => 'Возврат средств',
            self::DECISION_REPLACEMENT => 'Замена товара',
            self::DECISION_REJECTED => 'Отклонено',
            default => 'Не обработано',
        };
    }

    /**
     * Получить текст причины
     */
    public function getReasonText(): string
    {
        return match($this->reason) {
            self::REASON_INVALID_ACCOUNT => 'Невалидный аккаунт',
            self::REASON_WRONG_DATA => 'Неверные данные',
            self::REASON_NOT_WORKING => 'Не работает',
            self::REASON_ALREADY_USED => 'Уже использован',
            self::REASON_BANNED => 'Заблокирован',
            self::REASON_OTHER => 'Другое',
            default => 'Не указано',
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
            self::STATUS_NEW => 'Новая',
            self::STATUS_IN_REVIEW => 'На рассмотрении',
            self::STATUS_RESOLVED => 'Решена',
            self::STATUS_REJECTED => 'Отклонена',
            default => 'Неизвестно',
        };
    }
}
