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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_chats');
    }
};
