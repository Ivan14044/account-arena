<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Создаем таблицу для хранения истории изменений статуса заказов.
     * Обеспечивает полный аудит всех изменений статуса заказов.
     */
    public function up(): void
    {
        Schema::create('purchase_status_history', function (Blueprint $table) {
            $table->id();
            
            // Связь с заказом
            $table->foreignId('purchase_id')
                  ->constrained('purchases')
                  ->onDelete('cascade')
                  ->comment('ID заказа');
            
            // Статусы
            $table->string('old_status', 50)
                  ->nullable()
                  ->comment('Предыдущий статус');
            
            $table->string('new_status', 50)
                  ->comment('Новый статус');
            
            // Кто изменил статус
            $table->foreignId('changed_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('ID пользователя/администратора, который изменил статус (null для системных изменений)');
            
            // Причина изменения
            $table->text('reason')
                  ->nullable()
                  ->comment('Причина изменения статуса');
            
            // Дополнительные данные в формате JSON
            $table->json('metadata')
                  ->nullable()
                  ->comment('Дополнительные данные (например, account_data, processing_notes и т.д.)');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('purchase_id');
            $table->index('created_at');
            $table->index(['purchase_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_status_history');
    }
};
