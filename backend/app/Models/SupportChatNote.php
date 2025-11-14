<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_chat_id',
        'user_id',
        'note',
    ];

    /**
     * Связь с чатом
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }

    /**
     * Связь с администратором, создавшим заметку
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}