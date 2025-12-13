<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->decimal('amount', 12, 2)->comment('Сумма, причитающаяся поставщику');
            $table->enum('status', ['held', 'available', 'withdrawn', 'reversed'])->default('held');
            $table->timestamp('available_at')->nullable()->comment('Когда средства становятся доступными к выводу');
            $table->timestamp('processed_at')->nullable()->comment('Когда средства были выведены/списаны');
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('available_at');

            // FK: supplier -> users
            $table->foreign('supplier_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Optional FK constraints: если в вашей БД поддерживается и вы хотите
            // включить, раскомментируйте. Иначе уберите / замените на onDelete('set null').
            // $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            // $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_earnings');
    }
};
