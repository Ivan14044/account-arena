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
        Schema::table('categories', function (Blueprint $table) {
            // Колонка `type` добавляется более поздней миграцией
            // (2025_12_01_000000_add_type_to_categories_table). На чистой БД её здесь
            // ещё нет, поэтому позиционируем после `type` только если она существует —
            // иначе колонка просто добавляется в конец (позиция ни на что не влияет).
            $column = $table->unsignedBigInteger('parent_id')->nullable();
            if (Schema::hasColumn('categories', 'type')) {
                $column->after('type');
            }
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
