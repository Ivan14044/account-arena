<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_chat_id',
        'user_id',
        'sender_type',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Константы типов отправителей
    const SENDER_USER = 'user';
    const SENDER_ADMIN = 'admin';
    const SENDER_GUEST = 'guest';

    /**
     * Связь с чатом
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с вложениями
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportMessageAttachment::class, 'support_message_id');
    }

    /**
     * Связь с реакциями
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(SupportMessageReaction::class, 'support_message_id');
    }

    /**
     * Отметить сообщение как прочитанное
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Проверить, является ли сообщение от администратора
     */
    public function isFromAdmin(): bool
    {
        return $this->sender_type === self::SENDER_ADMIN;
    }

    /**
     * Проверить, является ли сообщение от пользователя
     */
    public function isFromUser(): bool
    {
        return $this->sender_type === self::SENDER_USER;
    }

    /**
     * Проверить, является ли сообщение от гостя
     */
    public function isFromGuest(): bool
    {
        return $this->sender_type === self::SENDER_GUEST;
    }

    /**
     * Scope для непрочитанных сообщений
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope для сообщений от пользователей/гостей (не от админов)
     */
    public function scopeFromUserOrGuest($query)
    {
        return $query->whereIn('sender_type', [self::SENDER_USER, self::SENDER_GUEST]);
    }
}
