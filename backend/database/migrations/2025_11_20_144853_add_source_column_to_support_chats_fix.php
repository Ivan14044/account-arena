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
        // Проверяем и добавляем колонку source
        if (!Schema::hasColumn('support_chats', 'source')) {
            Schema::table('support_chats', function (Blueprint $table) {
                $table->string('source')->default('website');
            });
        }
        
        // Проверяем и добавляем колонку telegram_chat_id
        if (!Schema::hasColumn('support_chats', 'telegram_chat_id')) {
            Schema::table('support_chats', function (Blueprint $table) {
                $table->bigInteger('telegram_chat_id')->nullable();
            });
        }
        
        // Добавляем индексы
        Schema::table('support_chats', function (Blueprint $table) {
            // Проверяем наличие индексов перед созданием
            if (!Schema::hasIndex('support_chats', 'support_chats_source_index')) {
                $table->index('source');
            }
            
            if (!Schema::hasIndex('support_chats', 'support_chats_telegram_chat_id_index')) {
                $table->index('telegram_chat_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropColumn(['source', 'telegram_chat_id']);
        });
    }
};
