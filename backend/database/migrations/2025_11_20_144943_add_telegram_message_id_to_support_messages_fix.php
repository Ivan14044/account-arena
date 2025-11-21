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
        // Проверяем и добавляем колонку telegram_message_id
        if (!Schema::hasColumn('support_messages', 'telegram_message_id')) {
            Schema::table('support_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('telegram_message_id')->nullable();
            });
        }
        
        // Добавляем индекс
        Schema::table('support_messages', function (Blueprint $table) {
            try {
                $table->index('telegram_message_id');
            } catch (\Exception $e) {
                // Индекс уже существует, игнорируем
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn('telegram_message_id');
        });
    }
};
