<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductDispute;
use App\Models\Option;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCloseDisputes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disputes:auto-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close silent disputes based on settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enabled = Option::get('dispute_auto_close_enabled', false);
        if (!$enabled) {
            $this->info('Auto-close feature is disabled.');
            return 0;
        }

        $hours = (int) Option::get('dispute_auto_close_hours', 24);
        if ($hours < 1) $hours = 24;

        $threshold = Carbon::now()->subHours($hours);

        $this->info("Checking for disputes older than {$hours} hours (before {$threshold})...");

        // 1. SCENARIO: Seller is silent on NEW dispute
        // If dispute is NEW and created_at < threshold -> Seller (Supplier/Admin) ignored it.
        // Action: Resolve as Refund (In favor of Buyer).
        
        $newDisputes = ProductDispute::where('status', ProductDispute::STATUS_NEW)
            ->where('created_at', '<', $threshold)
            ->get();

        $this->info("Found " . $newDisputes->count() . " obsolete NEW disputes.");

        foreach ($newDisputes as $dispute) {
            try {
                $this->info("Auto-closing dispute #{$dispute->id} (Seller silence)...");
                
                $comment = "Автоматическое решение: Продавец не ответил в течение {$hours} часов.";
                
                // FIX (M10): атрибутируем авто-возврат реальному администратору,
                // а не жёстко зашитому ID=1 (которого может не быть или он может
                // не быть админом → битый resolved_by / FK).
                $systemAdmin = \App\Models\User::where('is_admin', true)->orderBy('id')->first();
                if (!$systemAdmin) {
                    throw new \Exception('Не найден администратор для авто-закрытия претензии.');
                }

                // FIX (M10): задаём АКТУАЛЬНУЮ сумму возврата (как при ручной
                // обработке), иначе resolveWithRefund использует устаревшее/нулевое
                // refund_amount, сохранённое при создании претензии.
                if (!$dispute->transaction || $dispute->transaction->amount === null) {
                    throw new \Exception('У претензии нет транзакции с суммой — авто-возврат пропущен.');
                }
                $dispute->refund_amount = $dispute->transaction->amount;

                $dispute->resolveWithRefund($systemAdmin->id, $comment);
                
                Log::info("Dispute #{$dispute->id} auto-refunded due to seller inactivity.");
                
            } catch (\Exception $e) {
                $this->error("Failed to close dispute #{$dispute->id}: " . $e->getMessage());
                Log::error("Failed to auto-close dispute #{$dispute->id}", ['error' => $e->getMessage()]);
            }
        }

        // 2. SCENARIO: Buyer is silent?
        // Currently we don't have a specific status for 'Waiting for Buyer'.
        // If we had 'waiting_buyer', we would check 'updated_at' < threshold and Close (Reject).
        // Since we only have 'in_review', it is ambiguous. We SKIP this for now to avoid unfair rejections.

        $this->info('Done.');
        return 0;
    }
}
