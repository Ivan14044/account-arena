<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'notification_template_id',
        'read_at',
        'variables',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'variables' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id', 'id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
