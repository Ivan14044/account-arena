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
    public function getDecisionText(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }

        $translations = [
            'ru' => [
                self::DECISION_REFUND => 'Возврат средств',
                self::DECISION_REPLACEMENT => 'Замена товара',
                self::DECISION_REJECTED => 'Отклонено',
                'default' => 'Не обработано',
            ],
            'en' => [
                self::DECISION_REFUND => 'Refund',
                self::DECISION_REPLACEMENT => 'Replacement',
                self::DECISION_REJECTED => 'Rejected',
                'default' => 'Not processed',
            ],
            'uk' => [
                self::DECISION_REFUND => 'Повернення коштів',
                self::DECISION_REPLACEMENT => 'Заміна товару',
                self::DECISION_REJECTED => 'Відхилено',
                'default' => 'Не оброблено',
            ],
        ];

        $localeTranslations = $translations[$locale] ?? $translations['ru'];
        
        return match($this->admin_decision) {
            self::DECISION_REFUND => $localeTranslations[self::DECISION_REFUND],
            self::DECISION_REPLACEMENT => $localeTranslations[self::DECISION_REPLACEMENT],
            self::DECISION_REJECTED => $localeTranslations[self::DECISION_REJECTED],
            default => $localeTranslations['default'],
        };
    }

    /**
     * Получить текст причины
     */
    public function getReasonText(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }

        $translations = [
            'ru' => [
                self::REASON_INVALID_ACCOUNT => 'Невалидный аккаунт',
                self::REASON_WRONG_DATA => 'Неверные данные',
                self::REASON_NOT_WORKING => 'Не работает',
                self::REASON_ALREADY_USED => 'Уже использован',
                self::REASON_BANNED => 'Заблокирован',
                self::REASON_OTHER => 'Другое',
                'default' => 'Не указано',
            ],
            'en' => [
                self::REASON_INVALID_ACCOUNT => 'Invalid account',
                self::REASON_WRONG_DATA => 'Wrong data',
                self::REASON_NOT_WORKING => 'Not working',
                self::REASON_ALREADY_USED => 'Already used',
                self::REASON_BANNED => 'Banned',
                self::REASON_OTHER => 'Other',
                'default' => 'Not specified',
            ],
            'uk' => [
                self::REASON_INVALID_ACCOUNT => 'Невалідний акаунт',
                self::REASON_WRONG_DATA => 'Невірні дані',
                self::REASON_NOT_WORKING => 'Не працює',
                self::REASON_ALREADY_USED => 'Вже використано',
                self::REASON_BANNED => 'Заблоковано',
                self::REASON_OTHER => 'Інше',
                'default' => 'Не вказано',
            ],
        ];

        $localeTranslations = $translations[$locale] ?? $translations['ru'];
        
        return match($this->reason) {
            self::REASON_INVALID_ACCOUNT => $localeTranslations[self::REASON_INVALID_ACCOUNT],
            self::REASON_WRONG_DATA => $localeTranslations[self::REASON_WRONG_DATA],
            self::REASON_NOT_WORKING => $localeTranslations[self::REASON_NOT_WORKING],
            self::REASON_ALREADY_USED => $localeTranslations[self::REASON_ALREADY_USED],
            self::REASON_BANNED => $localeTranslations[self::REASON_BANNED],
            self::REASON_OTHER => $localeTranslations[self::REASON_OTHER],
            default => $localeTranslations['default'],
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
    public function getStatusText(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }

        $translations = [
            'ru' => [
                self::STATUS_NEW => 'Новая',
                self::STATUS_IN_REVIEW => 'На рассмотрении',
                self::STATUS_RESOLVED => 'Решена',
                self::STATUS_REJECTED => 'Отклонена',
                'default' => 'Неизвестно',
            ],
            'en' => [
                self::STATUS_NEW => 'New',
                self::STATUS_IN_REVIEW => 'In review',
                self::STATUS_RESOLVED => 'Resolved',
                self::STATUS_REJECTED => 'Rejected',
                'default' => 'Unknown',
            ],
            'uk' => [
                self::STATUS_NEW => 'Нова',
                self::STATUS_IN_REVIEW => 'На розгляді',
                self::STATUS_RESOLVED => 'Вирішено',
                self::STATUS_REJECTED => 'Відхилено',
                'default' => 'Невідомо',
            ],
        ];

        $localeTranslations = $translations[$locale] ?? $translations['ru'];
        
        return match($this->status) {
            self::STATUS_NEW => $localeTranslations[self::STATUS_NEW],
            self::STATUS_IN_REVIEW => $localeTranslations[self::STATUS_IN_REVIEW],
            self::STATUS_RESOLVED => $localeTranslations[self::STATUS_RESOLVED],
            self::STATUS_REJECTED => $localeTranslations[self::STATUS_REJECTED],
            default => $localeTranslations['default'],
        };
    }
}
