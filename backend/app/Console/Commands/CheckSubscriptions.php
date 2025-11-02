<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;
use App\Services\MonoPaymentService;

class CheckSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-payments';
    protected $description = 'Check subscriptions and process next payments if due';

    /**
     * Обрабатывает все активные подписки, у которых наступила дата следующего платежа.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $subscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('is_auto_renew', 1)
            ->where('next_payment_at', '<=', $now)
            ->get();

        foreach ($subscriptions as $subscription) {
            match ($subscription->payment_method) {
                'credit_card' => $this->processCreditCardPayment($subscription),
                default => $this->endSubscription($subscription),
            };
        }

        $this->info('Subscription check complete.');
        return Command::SUCCESS;
    }

    /**
     * Завершает подписку: меняет статус на ended и сохраняет.
     *
     * @param Subscription $subscription
     */
    private function endSubscription(Subscription $subscription): void
    {
        $subscription->status = Subscription::STATUS_ENDED;
        $subscription->save();

        $this->info("Subscription #{$subscription->id} ended (method: {$subscription->payment_method}).");
    }

    /**
     * Обрабатывает продление подписки через сохранённую карту.
     *
     * @param Subscription $subscription
     */
    private function processCreditCardPayment(Subscription $subscription): void
    {
        $this->info("Processing credit card payment for subscription #{$subscription->id}.");

        // Получаем первую сохранённую карту (если есть)
        $account = MonoPaymentService::getWalletCard('wallet_user_' . $subscription->user_id)[0] ?? null;

        /*if (empty($account)) {
            $this->error("No saved card found for subscription #{$subscription->id}. Subscription will be ended.");
            $this->endSubscription($subscription);
            return;
        }*/
        $account = [];
        // Пытаемся списать средства с карты
        $paymentSuccess = $this->attemptCharge($subscription, $account);

        if (!$paymentSuccess) {
            // увеличиваем количество попыток
            $subscription->payment_attempts = ($subscription->payment_attempts ?? 0) + 1;

            if ($subscription->payment_attempts >= 6) {
                $this->error("Final (6th) payment attempt failed for subscription #{$subscription->id}.");
                $this->endSubscription($subscription);
            } else {
                $subscription->save();
                $this->warn("Attempt #{$subscription->payment_attempts} failed for subscription #{$subscription->id}. Will retry later.");
            }

            return;
        }

        // Успешная оплата — сбрасываем счётчик и продлеваем подписку
        $subscription->payment_attempts = 0;
        $subscription->expiring_email_sent = 0;
        $subscription->next_payment_at = Carbon::now()->addMonth();
        $subscription->save();

        $this->info("Subscription #{$subscription->id} successfully renewed. Next payment at {$subscription->next_payment_at->toDateTimeString()}.");
    }

    /**
     * Пытается списать средства с карты пользователя.
     *
     * @param Subscription $subscription
     * @param array $account
     * @return bool
     */
    private function attemptCharge(Subscription $subscription, array $account): bool
    {
        // TODO: Заменить на реальный вызов MonoPaymentService::chargeWithToken($account['token'], $amount)
        return false;
    }
}
