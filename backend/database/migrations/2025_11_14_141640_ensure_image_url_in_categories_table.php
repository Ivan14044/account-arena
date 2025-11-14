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
        // Проверяем наличие колонки image_url и добавляем, если её нет
        if (!Schema::hasColumn('categories', 'image_url')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('image_url')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Не удаляем колонку при откате, так как она может быть нужна
        // Если нужно удалить, можно раскомментировать:
        // if (Schema::hasColumn('categories', 'image_url')) {
        //     Schema::table('categories', function (Blueprint $table) {
        //         $table->dropColumn('image_url');
        //     });
        // }
    }
};
