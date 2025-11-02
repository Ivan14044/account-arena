<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceAccount;

class DiagnoseServiceAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service-accounts:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose why service accounts are not showing on frontend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Diagnosing service accounts...');
        $this->newLine();
        
        $accounts = ServiceAccount::with('category')->get();
        
        if ($accounts->isEmpty()) {
            $this->warn('No service accounts found.');
            return 0;
        }
        
        $table = [];
        
        foreach ($accounts as $account) {
            $accountsData = $account->accounts_data;
            if (!is_array($accountsData)) {
                $accountsData = [];
            }
            
            $totalQuantity = count($accountsData);
            $soldCount = $account->used ?? 0;
            $availableCount = max(0, $totalQuantity - $soldCount);
            
            // Check why it might not be showing
            $issues = [];
            
            if (!$account->is_active) {
                $issues[] = 'NOT ACTIVE';
            }
            
            if ($account->title === null || trim($account->title) === '') {
                $issues[] = 'NO TITLE';
            }
            
            if ($account->price === null) {
                $issues[] = 'NO PRICE';
            }
            
            if (!is_array($account->accounts_data)) {
                $issues[] = 'INVALID accounts_data';
            }
            
            if ($availableCount <= 0) {
                $issues[] = 'NO STOCK (quantity=0)';
            }
            
            $status = empty($issues) ? '✓ OK' : '✗ ISSUES: ' . implode(', ', $issues);
            
            $table[] = [
                'ID' => $account->id,
                'Title' => $account->title ?: '(empty)',
                'Active' => $account->is_active ? 'Yes' : 'No',
                'Price' => $account->price ?? 'NULL',
                'Total Qty' => $totalQuantity,
                'Sold' => $soldCount,
                'Available' => $availableCount,
                'Status' => $status,
            ];
        }
        
        $this->table([
            'ID',
            'Title',
            'Active',
            'Price',
            'Total Qty',
            'Sold',
            'Available',
            'Status'
        ], $table);
        
        $this->newLine();
        $this->info('Products will show on frontend only if:');
        $this->line('  - is_active = true');
        $this->line('  - title is not NULL');
        $this->line('  - price is not NULL');
        $this->line('  - quantity > 0 (available items)');
        
        return 0;
    }
}
