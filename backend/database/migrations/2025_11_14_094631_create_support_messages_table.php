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
        // Check if table already exists (from previous migration 2025_01_15_100001)
        // If it exists, skip creation as the first migration already created the table
        // with all necessary columns and indexes
        if (!Schema::hasTable('support_messages')) {
            // Create table if it doesn't exist
            Schema::create('support_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('support_chat_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // null для сообщений от гостей
                $table->enum('sender_type', ['user', 'admin', 'guest'])->default('user');
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index('support_chat_id');
                $table->index('user_id');
                $table->index('is_read');
                $table->index('sender_type');
                $table->index('created_at');
            });
        }
        // If table exists, do nothing - first migration already created everything needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
