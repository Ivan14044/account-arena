<?php

namespace App\Services;

use App\Models\Promocode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromocodeService
{
    /**
     * Bulk create promocodes
     */
    public function bulkCreate(array $data): int
    {
        $quantity = (int) ($data['quantity'] ?? 1);
        $prefix = (string) ($data['prefix'] ?? '');
        $batchId = $data['batch_id'] ?? (string) Str::uuid();
        
        $promocodesToInsert = [];
        $codesInBatch = [];

        for ($i = 0; $i < $quantity; $i++) {
            $code = $prefix . $this->generateCode(8);
            
            if (isset($codesInBatch[$code])) {
                $i--;
                continue;
            }

            $codesInBatch[$code] = true;
            $promocodesToInsert[] = [
                'code' => $code,
                'type' => $data['type'],
                'prefix' => $prefix ?: null,
                'batch_id' => $batchId,
                'percent_discount' => $data['percent_discount'] ?? 0,
                'usage_limit' => $data['usage_limit'] ?? 0,
                'per_user_limit' => $data['per_user_limit'] ?? 1,
                'starts_at' => $data['starts_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Оптимизация: проверяем уникальность всех кодов одним запросом
        $codes = array_keys($codesInBatch);
        $existingCodes = Promocode::whereIn('code', $codes)->pluck('code')->toArray();
        
        if (!empty($existingCodes)) {
            // Если есть коллизии, просто фильтруем их (или можно было бы повторить генерацию только для них)
            $existingCodesMap = array_flip($existingCodes);
            $promocodesToInsert = array_filter($promocodesToInsert, function($item) use ($existingCodesMap) {
                return !isset($existingCodesMap[$item['code']]);
            });
        }

        if (!empty($promocodesToInsert)) {
            DB::transaction(function () use ($promocodesToInsert) {
                Promocode::insert($promocodesToInsert);
            });
        }

        return count($promocodesToInsert);
    }

    /**
     * Validate promocode for a user
     */
    public function validatePromocode(string $code, ?User $user): array
    {
        $promocode = Promocode::where('code', $code)->where('is_active', true)->first();

        if (!$promocode) {
            return ['success' => false, 'message' => 'Промокод не найден или неактивен.'];
        }

        if (!$promocode->canBeUsed()) {
            return ['success' => false, 'message' => 'Срок действия промокода истек или превышен лимит использования.'];
        }

        if ($user && !$promocode->canUserUse($user)) {
            return ['success' => false, 'message' => 'Вы уже использовали этот промокод максимально возможное количество раз.'];
        }

        return ['success' => true, 'promocode' => $promocode];
    }

    /**
     * Применение промокода внутри транзакции (защита от Race Condition)
     */
    public function applyPromocode(string $code, ?User $user): array
    {
        return DB::transaction(function () use ($code, $user) {
            // Блокируем запись промокода для обновления
            $promocode = Promocode::where('code', $code)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$promocode) {
                return ['success' => false, 'message' => 'Промокод не найден или неактивен.'];
            }

            // Проверка базовой валидности (лимиты, даты)
            if (!$promocode->canBeUsed()) {
                return ['success' => false, 'message' => 'Срок действия промокода истек или превышен лимит использования.'];
            }

            // Проверка лимита на пользователя
            if ($user && !$promocode->canUserUse($user)) {
                return ['success' => false, 'message' => 'Вы уже использовали этот промокод максимально возможное количество раз.'];
            }

            // Инкремент счетчика использований
            $promocode->increment('usage_count');

            // Запись истории использования
            if ($user) {
                DB::table('promocode_usages')->insert([
                    'promocode_id' => $promocode->id,
                    'user_id' => $user->id,
                    'used_at' => now(),
                ]);
            }

            return ['success' => true, 'promocode' => $promocode];
        });
    }

    /**
     * Generate a random alphanumeric code
     */
    public function generateCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}
