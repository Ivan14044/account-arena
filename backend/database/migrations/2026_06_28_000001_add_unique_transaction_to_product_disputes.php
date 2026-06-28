<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY FIX (H15 / bug BUG-02): на одну транзакцию допускается только одна
 * претензия (Transaction::dispute() — hasOne). Раньше уникальность держалась
 * только на проверке exists() в коде (TOCTOU), без БД-констрейнта, поэтому
 * параллельные запросы могли создать два спора и провернуть refund + replacement.
 * Добавляем уникальный индекс на transaction_id (с предварительной чисткой
 * дубликатов — оставляем самый ранний спор).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Чистим возможные дубликаты, оставляя спор с наименьшим id.
        $duplicates = DB::table('product_disputes')
            ->select('transaction_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('transaction_id')
            ->groupBy('transaction_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('product_disputes')
                ->where('transaction_id', $row->transaction_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        // 2) Уникальный индекс.
        Schema::table('product_disputes', function (Blueprint $table) {
            $table->unique('transaction_id', 'product_disputes_transaction_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_disputes', function (Blueprint $table) {
            $table->dropUnique('product_disputes_transaction_id_unique');
        });
    }
};
