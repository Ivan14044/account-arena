<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplier_earnings', function (Blueprint $table) {
            // ВАЖНО: Добавляем уникальный индекс для предотвращения дублирования earnings
            // для одной и той же покупки и транзакции
            // Используем whereNull для игнорирования NULL значений (так как purchase_id и transaction_id могут быть nullable)
            $table->unique(['purchase_id', 'transaction_id', 'supplier_id'], 'unique_purchase_transaction_supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_earnings', function (Blueprint $table) {
            $table->dropUnique('unique_purchase_transaction_supplier');
        });
    }
};
