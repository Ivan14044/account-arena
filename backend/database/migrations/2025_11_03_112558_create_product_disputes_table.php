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
        Schema::create('product_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('service_account_id')->nullable()->constrained()->onDelete('set null');
            
            // Причина претензии
            $table->enum('reason', [
                'invalid_account',      // Невалидный аккаунт
                'wrong_data',          // Неверные данные
                'not_working',         // Не работает
                'already_used',        // Уже использован
                'banned',              // Заблокирован
                'other'                // Другое
            ]);
            
            // Описание проблемы от клиента
            $table->text('customer_description');
            
            // Решение администратора
            $table->enum('admin_decision', ['refund', 'replacement', 'rejected'])->nullable();
            
            // Комментарий администратора
            $table->text('admin_comment')->nullable();
            
            // Сумма возврата
            $table->decimal('refund_amount', 10, 2)->nullable();
            
            // Статус претензии
            $table->enum('status', ['new', 'in_review', 'resolved', 'rejected'])->default('new');
            
            // Дата решения
            $table->timestamp('resolved_at')->nullable();
            
            // ID администратора, который обработал претензию
            $table->foreignId('resolved_by')->nullable()->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['user_id', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_disputes');
    }
};
