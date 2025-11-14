<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_chat_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Администратор, создавший заметку
            $table->text('note'); // Текст заметки
            $table->timestamps();
            
            $table->index('support_chat_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_notes');
    }
};