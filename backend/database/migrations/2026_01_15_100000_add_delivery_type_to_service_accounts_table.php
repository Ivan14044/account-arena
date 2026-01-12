<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляем поле delivery_type для определения способа выдачи товара
     */
    public function up(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->enum('delivery_type', ['automatic', 'manual'])
                  ->default('automatic')
                  ->after('is_active')
                  ->comment('Способ выдачи товара: automatic - автоматическая, manual - ручная');
            
            $table->text('manual_delivery_instructions')
                  ->nullable()
                  ->after('delivery_type')
                  ->comment('Инструкции для менеджера при ручной выдаче');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'manual_delivery_instructions']);
        });
    }
};
