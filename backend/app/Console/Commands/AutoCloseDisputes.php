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
                
                // Using the existing method to refund
                // Using Admin ID 1 (System/Main Admin) or null if allowed, usually better to pick a system user ID
                // For safety, we try to use ID 1, assuming it exists.
                $systemAdminId = 1; 

                $dispute->resolveWithRefund($systemAdminId, $comment);
                
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
