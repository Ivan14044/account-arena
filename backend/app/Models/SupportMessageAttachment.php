<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupportMessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'file_name',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
    ];

    /**
     * Связь с сообщением
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }

    /**
     * Получить полный URL файла
     */
    public function getFullUrlAttribute(): string
    {
        return $this->file_url ?? Storage::url($this->file_path);
    }

    /**
     * Проверить, является ли файл изображением
     */
    public function isImage(): bool
    {
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return in_array($this->mime_type, $imageMimes);
    }

    /**
     * Получить размер файла в человекочитаемом формате
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Неизвестно';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Удаление файла при удалении записи
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            // Удаляем файл из storage
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}