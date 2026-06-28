<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY FIX (H7 / bug H7): defense-in-depth против двойного учёта промокода
 * на один заказ. Идемпотентность уже обеспечена атомарным claim'ом вебхука (C6)
 * и проверкой order_id, но БД-уникальность на order_id гарантирует, что даже
 * при гонке/ретрае нельзя записать два использования на один order_id.
 * NULL order_id (balance-чекаут без invoice) допускает множественные значения.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Дедуп: оставляем самое раннее использование на каждый непустой order_id.
        $duplicates = DB::table('promocode_usages')
            ->select('order_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('promocode_usages')
                ->where('order_id', $row->order_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('promocode_usages', function (Blueprint $table) {
            $table->unique('order_id', 'promocode_usages_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('promocode_usages', function (Blueprint $table) {
            $table->dropUnique('promocode_usages_order_id_unique');
        });
    }
};
