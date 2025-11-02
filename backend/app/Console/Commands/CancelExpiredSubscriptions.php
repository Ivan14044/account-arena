<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class CancelExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:cancel-expired';
    protected $description = 'Cancel expired subscriptions without auto-renew';

    public function handle()
    {
        $now = Carbon::now();

        $subscriptions = Subscription::where('is_auto_renew', 0)
            ->where('status', 'active')
            ->where('next_payment_at', '<', $now)
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->status = Subscription::STATUS_CANCELED;
            $subscription->save();

            $this->info("Subscription #{$subscription->id} canceled.");
        }

        $this->info("Done. " . $subscriptions->count() . " subscriptions processed.");
    }
}
