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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Кто купил
            $table->foreignId('service_account_id')->constrained()->onDelete('cascade'); // Какой товар
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null'); // Связь с транзакцией
            $table->integer('quantity')->default(1); // Количество купленных единиц
            $table->decimal('price', 10, 2); // Цена за единицу
            $table->decimal('total_amount', 10, 2); // Общая сумма
            $table->json('account_data'); // Выданные данные аккаунтов (массив)
            $table->string('status')->default('completed'); // completed, pending, failed
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
