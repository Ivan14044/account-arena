<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use App\Services\EmailService;

class SendExpiringSubscriptionEmails extends Command
{
    protected $signature = 'subscriptions:notify-expiring';
    
    protected $description = 'Send email notifications for subscriptions expiring in 3 days';

    public function handle(): int
    {
        $targetDate = Carbon::now()->addDays(3)->startOfDay();

        $subscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('next_payment_at', '<=', $targetDate)
            ->where('expiring_email_sent', false)
            ->with(['user', 'service.translations'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $service = $subscription->service;

            $serviceName = $service?->getTranslation('name', $user->lang ?? 'en') ?? $service?->name;

            EmailService::send('subscription_expiring', $user->id, [
                'service_name' => $serviceName
            ]);

            $subscription->expiring_email_sent = true;
            $subscription->save();
        }

        $this->info("Checked " . $subscriptions->count() . " subscriptions.");
        return 0;
    }
}
