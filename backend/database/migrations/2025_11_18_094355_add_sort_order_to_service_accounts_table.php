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
        Schema::table('service_accounts', function (Blueprint $table) {
            // Добавляем поле для сортировки товаров
            $table->integer('sort_order')->default(0)->after('id');
            $table->index('sort_order'); // Индекс для быстрой сортировки
        });
        
        // Установить начальные значения sort_order равными id
        // Это сохранит текущий порядок (новые товары будут в конце)
        DB::statement('UPDATE service_accounts SET sort_order = id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
