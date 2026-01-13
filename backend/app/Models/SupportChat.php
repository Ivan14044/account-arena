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
        'source',
        'telegram_chat_id',
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
    
    // Константы источников
    const SOURCE_WEBSITE = 'website';
    const SOURCE_TELEGRAM = 'telegram';

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
    
    /**
     * Scope для чатов из Telegram
     */
    public function scopeFromTelegram($query)
    {
        return $query->where('source', self::SOURCE_TELEGRAM);
    }
    
    /**
     * Scope для чатов с сайта
     */
    public function scopeFromWebsite($query)
    {
        return $query->where('source', self::SOURCE_WEBSITE);
    }
    
    /**
     * Проверить, является ли чат из Telegram
     */
    public function isFromTelegram(): bool
    {
        return $this->source === self::SOURCE_TELEGRAM;
    }
    
    /**
     * Получить количество непрочитанных сообщений от пользователей/гостей (не от админов)
     */
    public function getUnreadMessagesCount(): int
    {
        // Кэшируем результат на 60 секунд для уменьшения нагрузки на БД
        return \Illuminate\Support\Facades\Cache::remember(
            "chat_unread_count_{$this->id}",
            60,
            function() {
                return $this->messages()
                    ->where('is_read', false)
                    ->whereIn('sender_type', [SupportMessage::SENDER_USER, SupportMessage::SENDER_GUEST])
                    ->count();
            }
        );
    }
    
    /**
     * Очистить кеш количества непрочитанных сообщений для этого чата
     */
    public function clearUnreadCountCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("chat_unread_count_{$this->id}");
        \Illuminate\Support\Facades\Cache::forget('support_chats_unread_count');
    }

    /**
     * Получить общий размер всех вложений в чате (в байтах)
     */
    public function getTotalAttachmentsSize(): int
    {
        return SupportMessageAttachment::whereHas('message', function($query) {
            $query->where('support_chat_id', $this->id);
        })->sum('file_size');
    }
}
