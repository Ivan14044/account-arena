<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_email',
        'guest_name',
        'status',
        'assigned_to',
        'last_message_at',
        'rating',
        'rating_comment',
        'rated_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'rated_at' => 'datetime',
        'rating' => 'integer',
    ];

    // Константы статусов
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_PENDING = 'pending';

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с назначенным администратором
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Связь с сообщениями
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Получить последнее сообщение
     */
    public function lastMessage()
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    /**
     * Связь с заметками администраторов
     */
    public function notes(): HasMany
    {
        return $this->hasMany(SupportChatNote::class, 'support_chat_id');
    }

    /**
     * Проверить, является ли чат гостевым
     */
    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Получить email пользователя
     */
    public function getEmailAttribute(): ?string
    {
        return $this->user ? $this->user->email : $this->guest_email;
    }

    /**
     * Получить имя пользователя
     */
    public function getNameAttribute(): ?string
    {
        return $this->user ? $this->user->name : $this->guest_name;
    }

    /**
     * Scope для активных чатов (не закрытых)
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_CLOSED);
    }

    /**
     * Scope для незакрытых чатов (open или pending)
     */
    public function scopeNotClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_PENDING]);
    }
}
