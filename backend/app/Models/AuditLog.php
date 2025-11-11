<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель для audit trail - отслеживание действий администраторов
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'model_type',
        'model_id',
        'user_id',
        'changes',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Связь с пользователем, который выполнил действие
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Создать запись audit log
     */
    public static function log(
        string $action,
        string $modelType,
        ?int $modelId,
        ?int $userId,
        ?array $changes = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'user_id' => $userId,
            'changes' => $changes,
            'ip' => $ip ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}



