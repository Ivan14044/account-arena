<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Защита: если колонка уже есть — не добавляем (чтобы миграция не падала)
        if (! Schema::hasColumn('users', 'supplier_hold_hours')) {
            Schema::table('users', function (Blueprint $table) {
                // Холд в часах, по-умолчанию 6 часов
                $table->unsignedInteger('supplier_hold_hours')->default(6)->after('supplier_commission');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'supplier_hold_hours')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('supplier_hold_hours');
            });
        }
    }
};
