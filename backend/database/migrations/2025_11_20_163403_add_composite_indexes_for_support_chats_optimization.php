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
        Schema::table('support_messages', function (Blueprint $table) {
            // Составной индекс для частых запросов: поиск непрочитанных сообщений в чате
            // Используется в: ->where('support_chat_id', $id)->where('is_read', false)->whereIn('sender_type', ...)
            if (!$this->indexExists('support_messages', 'support_messages_chat_read_sender_idx')) {
                $table->index(['support_chat_id', 'is_read', 'sender_type'], 'support_messages_chat_read_sender_idx');
            }
            
            // Составной индекс для сортировки по дате создания в чате
            // Используется в: ->where('support_chat_id', $id)->orderBy('created_at')
            if (!$this->indexExists('support_messages', 'support_messages_chat_created_idx')) {
                $table->index(['support_chat_id', 'created_at'], 'support_messages_chat_created_idx');
            }
        });

        Schema::table('support_chats', function (Blueprint $table) {
            // Составной индекс для списка чатов с фильтрацией по источнику и статусу
            // Используется в: ->where('source', $source)->where('status', $status)->orderBy('last_message_at')
            if (!$this->indexExists('support_chats', 'support_chats_source_status_message_idx')) {
                $table->index(['source', 'status', 'last_message_at'], 'support_chats_source_status_message_idx');
            }
            
            // Составной индекс для поиска чатов пользователя по статусу
            // Используется в: ->where('user_id', $id)->where('status', '!=', 'closed')->orderBy('created_at')
            if (!$this->indexExists('support_chats', 'support_chats_user_status_created_idx')) {
                $table->index(['user_id', 'status', 'created_at'], 'support_chats_user_status_created_idx');
            }
            
            // Составной индекс для Telegram чатов
            // Используется в: ->where('source', 'telegram')->where('telegram_chat_id', $id)
            if (!$this->indexExists('support_chats', 'support_chats_telegram_source_idx')) {
                $table->index(['source', 'telegram_chat_id'], 'support_chats_telegram_source_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if ($this->indexExists('support_messages', 'support_messages_chat_read_sender_idx')) {
                $table->dropIndex('support_messages_chat_read_sender_idx');
            }
            if ($this->indexExists('support_messages', 'support_messages_chat_created_idx')) {
                $table->dropIndex('support_messages_chat_created_idx');
            }
        });

        Schema::table('support_chats', function (Blueprint $table) {
            if ($this->indexExists('support_chats', 'support_chats_source_status_message_idx')) {
                $table->dropIndex('support_chats_source_status_message_idx');
            }
            if ($this->indexExists('support_chats', 'support_chats_user_status_created_idx')) {
                $table->dropIndex('support_chats_user_status_created_idx');
            }
            if ($this->indexExists('support_chats', 'support_chats_telegram_source_idx')) {
                $table->dropIndex('support_chats_telegram_source_idx');
            }
        });
    }
    
    /**
     * Проверить существование индекса
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return isset($result[0]) && $result[0]->count > 0;
    }
};
