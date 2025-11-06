<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            // Добавляем поле артикула (SKU)
            $table->string('sku', 50)->nullable()->unique()->after('id');
            $table->index('sku'); // Индекс для быстрого поиска по артикулу
        });

        // Генерируем артикулы для существующих товаров
        $serviceAccounts = DB::table('service_accounts')->whereNull('sku')->get();
        
        foreach ($serviceAccounts as $account) {
            // Генерируем уникальный артикул формата: PRD-{ID}-{RANDOM}
            $sku = 'PRD-' . str_pad($account->id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4));
            
            DB::table('service_accounts')
                ->where('id', $account->id)
                ->update(['sku' => $sku]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropColumn('sku');
        });
    }
};
