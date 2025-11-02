<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceAccount;

class FixServiceAccountsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service-accounts:fix-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix service accounts with NULL or invalid accounts_data field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking service accounts...');
        
        $accounts = ServiceAccount::all();
        $fixed = 0;
        $skipped = 0;
        
        foreach ($accounts as $account) {
            $needsFix = false;
            $issues = [];
            
            // Check if accounts_data is NULL or not an array
            $accountsData = $account->accounts_data;
            if ($accountsData === null) {
                $needsFix = true;
                $issues[] = 'accounts_data is NULL';
            } elseif (!is_array($accountsData)) {
                $needsFix = true;
                $issues[] = 'accounts_data is not an array';
            }
            
            // Check if used is NULL
            if ($account->used === null) {
                $needsFix = true;
                $issues[] = 'used is NULL';
            }
            
            if ($needsFix) {
                $this->line("Fixing account ID {$account->id}: " . implode(', ', $issues));
                
                if ($accountsData === null || !is_array($accountsData)) {
                    $account->accounts_data = [];
                }
                
                if ($account->used === null) {
                    $account->used = 0;
                }
                
                $account->save();
                $fixed++;
            } else {
                $skipped++;
            }
        }
        
        $this->info("Fixed: {$fixed} accounts");
        $this->info("Skipped: {$skipped} accounts (already correct)");
        
        return 0;
    }
}
