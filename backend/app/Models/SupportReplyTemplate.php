<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportReplyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'usage_count',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Увеличить счетчик использования
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope для активных шаблонов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('title');
    }
}