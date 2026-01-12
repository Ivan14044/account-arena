<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляем поле last_reminder_at для отслеживания последнего напоминания о просроченных заказах
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('last_reminder_at')
                  ->nullable()
                  ->after('processed_at')
                  ->comment('Дата и время последнего напоминания о просроченном заказе');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('last_reminder_at');
        });
    }
};
