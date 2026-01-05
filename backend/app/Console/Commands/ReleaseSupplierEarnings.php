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
    public function handle()
    {
        $this->info('Начинаем перевод средств поставщиков...');

        // Находим все earnings, которые готовы к переводу (held и available_at <= now)
        $readyToRelease = SupplierEarning::readyToRelease()
            ->with('supplier')
            ->get();

        if ($readyToRelease->isEmpty()) {
            $this->info('Нет средств, готовых к переводу.');
            return 0;
        }

        $this->info("Найдено {$readyToRelease->count()} записей для перевода.");

        $processed = 0;
        $errors = 0;

        // Группируем по поставщикам для оптимизации
        $earningsBySupplier = $readyToRelease->groupBy('supplier_id');

        foreach ($earningsBySupplier as $supplierId => $earnings) {
            try {
                DB::transaction(function () use ($supplierId, $earnings, &$processed) {
                    $supplier = User::lockForUpdate()->find($supplierId);
                    
                    if (!$supplier || !$supplier->is_supplier) {
                        $this->warn("Поставщик {$supplierId} не найден или не является поставщиком.");
                        return;
                    }

                    $totalAmount = $earnings->sum('amount');

                    // Обновляем статус всех earnings на 'available'
                    $earnings->each(function ($earning) {
                        $earning->update([
                            'status' => 'available',
                            'processed_at' => now(),
                        ]);
                    });

                    // Увеличиваем баланс поставщика
                    $supplier->increment('supplier_balance', $totalAmount);

                    $processed += $earnings->count();

                    Log::info('Supplier earnings released', [
                        'supplier_id' => $supplierId,
                        'earnings_count' => $earnings->count(),
                        'total_amount' => $totalAmount,
                        'new_balance' => $supplier->fresh()->supplier_balance,
                    ]);

                    $this->info("Поставщик {$supplierId}: переведено {$earnings->count()} записей на сумму {$totalAmount} USD");
                });
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Failed to release supplier earnings', [
                    'supplier_id' => $supplierId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Ошибка при переводе средств поставщика {$supplierId}: {$e->getMessage()}");
            }
        }

        $this->info("Обработка завершена. Обработано: {$processed}, Ошибок: {$errors}");

        return 0;
    }
}
