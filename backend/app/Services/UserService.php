<?php

namespace App\Services;

use App\Models\User;
use App\Models\Purchase;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Update user profile and security settings
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Handle password hashing if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Handle is_blocked logic (matching controller logic)
            if (isset($data['is_blocked'])) {
                if ($data['is_blocked'] == 2) {
                    $data['is_blocked'] = 0;
                    $data['is_pending'] = 1;
                } elseif ($data['is_blocked'] == 0) {
                    $data['is_blocked'] = 0;
                    $data['is_pending'] = 0;
                }
            }

            // ВАЖНО: Защита баланса поставщика от race condition и случайной перезаписи.
            // Если баланс передан в данных, мы обновляем его атомарно через разницу.
            if (isset($data['supplier_balance'])) {
                $newSupplierBalance = (float)$data['supplier_balance'];
                $currentUser = User::where('id', $user->id)->lockForUpdate()->first();
                $diff = $newSupplierBalance - (float)$currentUser->supplier_balance;
                
                if ($diff != 0) {
                    $currentUser->increment('supplier_balance', $diff);
                }
                unset($data['supplier_balance']);
            }

            $user->update($data);
            return $user->fresh();
        });
    }

    /**
     * Get user purchase history with eager loading
     */
    public function getPurchaseHistory(User $user)
    {
        return $user->purchases()
            ->with(['serviceAccount', 'transaction'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'] ?? explode('@', $data['email'])[0],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_blocked' => 0,
            'is_pending' => 0,
        ]);
    }
}
