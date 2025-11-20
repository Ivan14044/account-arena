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
            // Источник чата: 'website' или 'telegram'
            if (!Schema::hasColumn('support_chats', 'source')) {
                $table->string('source')->default('website');
            }
            
            // ID чата в Telegram (числовой, не username)
            if (!Schema::hasColumn('support_chats', 'telegram_chat_id')) {
                $table->bigInteger('telegram_chat_id')->nullable();
            }
        });
        
        // Добавляем индексы отдельно (после добавления колонок)
        Schema::table('support_chats', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('support_chats');
            
            if (!isset($indexes['support_chats_source_index'])) {
                $table->index('source');
            }
            if (!isset($indexes['support_chats_telegram_chat_id_index'])) {
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
            $table->dropIndex(['source']);
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn(['source', 'telegram_chat_id']);
        });
    }
};
