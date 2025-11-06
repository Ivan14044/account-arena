<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обновляем существующие транзакции, добавляя service_account_id из связанных покупок
        // Это исправляет проблему "Эта покупка не поддерживает претензии" для старых данных
        
        // Получаем все покупки, у которых транзакция не имеет service_account_id
        $purchases = DB::table('purchases')
            ->whereNotNull('service_account_id')
            ->get();
        
        $updatedCount = 0;
        
        foreach ($purchases as $purchase) {
            // Обновляем транзакцию, добавляя service_account_id из покупки
            $updated = DB::table('transactions')
                ->where('id', $purchase->transaction_id)
                ->whereNull('service_account_id')
                ->update(['service_account_id' => $purchase->service_account_id]);
            
            if ($updated) {
                $updatedCount++;
            }
        }
        
        \Log::info("Миграция: Обновлено транзакций с service_account_id: {$updatedCount}");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откат не требуется, так как мы только заполняем пустые значения
        // и не удаляем данные
    }
};

