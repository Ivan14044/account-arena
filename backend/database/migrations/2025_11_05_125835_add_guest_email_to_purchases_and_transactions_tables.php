<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляем возможность покупок без регистрации:
     * - guest_email для хранения email гостевых покупателей
     * - делаем user_id nullable для поддержки гостевых заказов
     */
    public function up(): void
    {
        // Обновляем таблицу purchases
        Schema::table('purchases', function (Blueprint $table) {
            // Добавляем поле для email гостя
            $table->string('guest_email')->nullable()->after('user_id');
            
            // Делаем user_id nullable (для гостевых покупок)
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
        
        // Обновляем таблицу transactions
        Schema::table('transactions', function (Blueprint $table) {
            // Добавляем поле для email гостя
            $table->string('guest_email')->nullable()->after('user_id');
            
            // Делаем user_id nullable (для гостевых транзакций)
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
        
        // Добавляем индексы для быстрого поиска по email
        Schema::table('purchases', function (Blueprint $table) {
            $table->index('guest_email');
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('guest_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откатываем изменения в purchases
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['guest_email']);
            $table->dropColumn('guest_email');
            
            // Возвращаем обязательность user_id (если нужно)
            // Внимание: это может привести к ошибкам, если есть гостевые записи
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
        
        // Откатываем изменения в transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['guest_email']);
            $table->dropColumn('guest_email');
            
            // Возвращаем обязательность user_id
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
