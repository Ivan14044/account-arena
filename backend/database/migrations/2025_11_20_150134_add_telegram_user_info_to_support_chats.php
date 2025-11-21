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
        Schema::table('support_chats', function (Blueprint $table) {
            // Имя пользователя из Telegram (без никнейма для защиты конфиденциальности)
            if (!Schema::hasColumn('support_chats', 'telegram_first_name')) {
                $table->string('telegram_first_name')->nullable();
            }
            
            if (!Schema::hasColumn('support_chats', 'telegram_last_name')) {
                $table->string('telegram_last_name')->nullable();
            }
            
            // Путь к аватарке пользователя
            if (!Schema::hasColumn('support_chats', 'telegram_photo')) {
                $table->string('telegram_photo')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropColumn(['telegram_first_name', 'telegram_last_name', 'telegram_photo']);
        });
    }
};
