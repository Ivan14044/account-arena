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
        
        // ВАЖНО: Оптимизируем массовое создание. Генерируем коды в памяти и делаем один bulk insert.
        $existingCodes = Promocode::pluck('code')->toArray();
        $promocodesToInsert = [];
        $codesInBatch = [];

        DB::transaction(function () use ($quantity, $prefix, $data, $batchId, &$promocodesToInsert, $existingCodes, &$codesInBatch) {
            for ($i = 0; $i < $quantity; $i++) {
                do {
                    $code = $prefix . $this->generateCode(8);
                } while (in_array($code, $existingCodes) || in_array($code, $codesInBatch));

                $codesInBatch[] = $code;

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

            Promocode::insert($promocodesToInsert);
        });

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

        if (!$promocode->isValid()) {
            return ['success' => false, 'message' => 'Срок действия промокода истек или превышен лимит использования.'];
        }

        if ($user && !$promocode->canUserUse($user)) {
            return ['success' => false, 'message' => 'Вы уже использовали этот промокод максимально возможное количество раз.'];
        }

        return ['success' => true, 'promocode' => $promocode];
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
