<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ServiceAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignServiceAccount
{
    public function assignToUser(int $serviceId, User $user): ?ServiceAccount
    {
        $minExpiry = Carbon::now()->addHours(3);

        $existing = DB::table('user_service_accounts')
            ->join('service_accounts', 'service_accounts.id', '=', 'user_service_accounts.service_account_id')
            ->where('user_service_accounts.user_id', $user->id)
            ->where('service_accounts.service_id', $serviceId)
            ->where('service_accounts.is_active', true)
            ->where(function ($q) use ($minExpiry) {
                $q->whereNull('service_accounts.expiring_at')
                    ->orWhere('service_accounts.expiring_at', '>=', $minExpiry);
            })
            ->select('service_accounts.*')
            ->first();

        if ($existing) {
            return ServiceAccount::find($existing->id);
        }

        $newAccount = ServiceAccount::where('service_id', $serviceId)
            ->where('is_active', true)
            ->where(function ($q) use ($minExpiry) {
                $q->whereNull('expiring_at')
                    ->orWhere('expiring_at', '>=', $minExpiry);
            })
            ->orderBy('used', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if (!$newAccount) {
            return null;
        }

        DB::table('user_service_accounts')->insert([
            'user_id' => $user->id,
            'service_account_id' => $newAccount->id
        ]);

        $newAccount->increment('used');

        return $newAccount;
    }
}
