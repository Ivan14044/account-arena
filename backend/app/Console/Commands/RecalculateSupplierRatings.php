<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RecalculateSupplierRatings extends Command
{
    protected $signature = 'suppliers:recalculate-ratings';
    protected $description = 'Recalculate ratings for all suppliers';

    public function handle()
    {
        $this->info('Recalculating supplier ratings...');
        $this->newLine();

        $suppliers = User::where('is_supplier', true)
            ->where('is_blocked', false)
            ->get();

        $bar = $this->output->createProgressBar($suppliers->count());
        $bar->start();

        foreach ($suppliers as $supplier) {
            $oldRating = $supplier->supplier_rating ?? 100;
            $newRating = $supplier->calculateSupplierRating();
            
            if ($oldRating != $newRating) {
                $this->newLine();
                $this->info("✓ {$supplier->name}: {$oldRating}% → {$newRating}%");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();
        $this->info("✓ Successfully recalculated ratings for {$suppliers->count()} suppliers!");

        return 0;
    }
}
