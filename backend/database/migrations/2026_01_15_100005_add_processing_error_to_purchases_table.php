<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляем поле processing_error для хранения ошибок обработки заказов
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->text('processing_error')
                  ->nullable()
                  ->after('admin_notes')
                  ->comment('Ошибка обработки заказа (если возникла)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('processing_error');
        });
    }
};
