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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // create, update, delete
            $table->string('model_type'); // User, ServiceAccount, Purchase, etc
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Кто выполнил действие
            $table->json('changes')->nullable(); // Изменения (old/new values)
            $table->string('ip', 45)->nullable(); // IPv4/IPv6
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('created_at');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
