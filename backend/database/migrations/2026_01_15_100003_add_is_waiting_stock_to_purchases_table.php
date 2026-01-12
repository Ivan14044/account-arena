<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляем поле is_waiting_stock для отслеживания заказов, ожидающих появления товара
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->boolean('is_waiting_stock')
                  ->default(false)
                  ->after('status')
                  ->comment('Флаг ожидания товара: true - товар отсутствует, заказ ожидает появления товара');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('is_waiting_stock');
        });
    }
};
