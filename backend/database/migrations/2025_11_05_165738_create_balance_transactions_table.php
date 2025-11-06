<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Создаем таблицу для хранения истории всех операций с балансом пользователя.
     * Эта таблица обеспечивает полную прозрачность и аудит всех изменений баланса.
     */
    public function up(): void
    {
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            
            // Связь с пользователем
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Тип операции (topup_card, topup_crypto, deduction, refund, purchase и т.д.)
            $table->string('type', 50)->index();
            
            // Сумма операции (положительная для пополнения, отрицательная для списания)
            $table->decimal('amount', 15, 2);
            
            // Баланс до операции
            $table->decimal('balance_before', 15, 2)->default(0);
            
            // Баланс после операции
            $table->decimal('balance_after', 15, 2)->default(0);
            
            // Статус операции (pending, completed, failed, cancelled)
            $table->string('status', 20)->default('completed')->index();
            
            // Описание операции на русском языке
            $table->text('description')->nullable();
            
            // Дополнительные данные в формате JSON
            // (invoice_id, order_id, payment_method, admin_user_id и т.д.)
            $table->json('metadata')->nullable();
            
            // Временные метки
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
