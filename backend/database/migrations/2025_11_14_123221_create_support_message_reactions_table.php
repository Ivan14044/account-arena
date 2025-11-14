<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Для админов
            $table->string('emoji', 10); // Emoji символ
            $table->string('reaction_type')->default('user'); // user, admin, guest
            $table->string('reaction_identifier')->nullable(); // email для гостей или user_id для пользователей
            $table->timestamps();
            
            // Уникальность: один пользователь может добавить одну реакцию одного типа на сообщение
            $table->unique(['support_message_id', 'reaction_identifier', 'emoji'], 'unique_reaction');
            
            $table->index('support_message_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_message_reactions');
    }
};