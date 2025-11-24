<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('support_messages', 'telegram_message_id')) {
                $table->unsignedBigInteger('telegram_message_id')->nullable()->after('message');
            }

            if (!$this->indexExists('support_messages', 'support_messages_telegram_message_id_index')) {
                $table->index('telegram_message_id');
            }
        });

        Schema::table('support_chats', function (Blueprint $table) {
            if (Schema::hasColumn('support_chats', 'source') && !$this->indexExists('support_chats', 'support_chats_source_index')) {
                $table->index('source');
            }

            if (Schema::hasColumn('support_chats', 'telegram_chat_id') && !$this->indexExists('support_chats', 'support_chats_telegram_chat_id_index')) {
                $table->index('telegram_chat_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if ($this->indexExists('support_messages', 'support_messages_telegram_message_id_index')) {
                $table->dropIndex('support_messages_telegram_message_id_index');
            }

            if (Schema::hasColumn('support_messages', 'telegram_message_id')) {
                $table->dropColumn('telegram_message_id');
            }
        });

        Schema::table('support_chats', function (Blueprint $table) {
            if ($this->indexExists('support_chats', 'support_chats_source_index')) {
                $table->dropIndex('support_chats_source_index');
            }

            if ($this->indexExists('support_chats', 'support_chats_telegram_chat_id_index')) {
                $table->dropIndex('support_chats_telegram_chat_id_index');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $prefixedTable = $connection->getTablePrefix() . $table;

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list({$prefixedTable})");
            foreach ($indexes as $idx) {
                if ((string)($idx->name ?? '') === $index) {
                    return true;
                }
            }
            return false;
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT COUNT(*) AS cnt 
             FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $prefixedTable, $index]
        );

        return !empty($result) && $result[0]->cnt > 0;
    }
};

