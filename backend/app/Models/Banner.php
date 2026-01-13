<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_en',
        'title_uk',
        'image_url',
        'link',
        'position',
        'order',
        'is_active',
        'open_new_tab',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_new_tab' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'order' => 'integer',
    ];

    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Если это уже полный URL (начинается с http), возвращаем как есть
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Убираем начальный слеш, если есть
        $value = ltrim($value, '/');
        
        // Если путь начинается с 'storage/', убираем его (Storage::url уже добавляет storage/)
        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, 8);
        }

        // Формируем полный URL через Storage
        return \Illuminate\Support\Facades\Storage::disk('public')->url($value);
    }

    /**
     * Scope to get only active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope to get banners by position
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Check if banner is currently active (within date range)
     */
    public function isCurrentlyActive()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Get available positions for banners
     */
    public static function getPositions()
    {
        return [
            'home_top_wide' => 'Широкий баннер на главной странице (1 штука)',
            'home_top' => 'Баннеры на главной странице (4 штуки)',
        ];
    }
}
