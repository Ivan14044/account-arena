<?php

namespace App\Console\Commands;

use App\Models\SupplierEarning;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseSupplierEarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:release-earnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Переводит средства из SupplierEarning (held) в supplier_balance после окончания холда';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\BalanceService $balanceService)
    {
        $this->info('Начинаем перевод средств поставщиков...');

        // Находим всех поставщиков, у которых есть средства в холде, готовые к переводу
        $supplierIds = SupplierEarning::readyToRelease()
            ->distinct()
            ->pluck('supplier_id');

        if ($supplierIds->isEmpty()) {
            $this->info('Нет средств, готовых к переводу.');
            return 0;
        }

        $this->info("Найдено " . $supplierIds->count() . " поставщиков для обработки.");

        $processedSuppliers = 0;
        $totalAmountReleased = 0;

        foreach ($supplierIds as $supplierId) {
            try {
                $supplier = User::find($supplierId);
                if (!$supplier || !$supplier->is_supplier) {
                    continue;
                }

                $releasedAmount = $balanceService->syncSupplierBalance($supplier);
                
                if ($releasedAmount > 0) {
                    $processedSuppliers++;
                    $totalAmountReleased += $releasedAmount;
                    $this->info("Поставщик {$supplierId}: переведено " . number_format($releasedAmount, 2) . " USD");
                }
            } catch (\Throwable $e) {
                Log::error('Failed to release supplier earnings in command', [
                    'supplier_id' => $supplierId,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Ошибка при переводе средств поставщика {$supplierId}: {$e->getMessage()}");
            }
        }

        $this->info("Обработка завершена. Обработано поставщиков: {$processedSuppliers}, Общая сумма: " . number_format($totalAmountReleased, 2) . " USD");

        return 0;
    }
}
