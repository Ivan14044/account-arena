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
        // Check if table already exists (from previous migration)
        if (Schema::hasTable('support_chats')) {
            // Add missing columns if they don't exist
            Schema::table('support_chats', function (Blueprint $table) {
                if (!Schema::hasColumn('support_chats', 'subject')) {
                    $table->string('subject')->nullable()->after('guest_name');
                }
            });
            
            // Update default status if needed
            // Note: Changing enum default requires raw SQL in some MySQL versions
            // This is handled separately if needed
        } else {
            // Create table if it doesn't exist
            Schema::create('support_chats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('guest_email')->nullable(); // Для гостевых чатов
                $table->string('guest_name')->nullable(); // Имя гостя
                $table->string('subject')->nullable(); // Тема чата
                $table->enum('status', ['open', 'closed', 'pending'])->default('open');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Администратор
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('status');
                $table->index('assigned_to');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_chats');
    }
};
