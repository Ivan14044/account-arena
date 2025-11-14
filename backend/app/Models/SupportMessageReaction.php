<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'user_id',
        'emoji',
        'reaction_type',
        'reaction_identifier',
    ];

    /**
     * Связь с сообщением
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }

    /**
     * Связь с пользователем (для админов и авторизованных пользователей)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}