<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Расширяем таблицу purchases для поддержки ручной выдачи:
     * - Расширяем статусы (processing для ручной обработки)
     * - Добавляем поля для отслеживания обработки
     */
    public function up(): void
    {
        // Для MySQL/MariaDB используем MODIFY COLUMN для изменения типа
        // Для других БД может потребоваться другой подход
        // Сначала обновляем существующие статусы 'pending' и 'failed' если они есть
        DB::statement("UPDATE purchases SET status = 'completed' WHERE status NOT IN ('completed', 'pending', 'failed')");
        
        // Изменяем тип колонки на enum с расширенными статусами
        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'completed'");
        
        Schema::table('purchases', function (Blueprint $table) {
            // Добавляем поля для ручной обработки
            $table->foreignId('processed_by')
                  ->nullable()
                  ->after('status')
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('ID администратора, который обработал заказ');
            
            $table->timestamp('processed_at')
                  ->nullable()
                  ->after('processed_by')
                  ->comment('Дата и время обработки заказа');
            
            $table->text('processing_notes')
                  ->nullable()
                  ->after('processed_at')
                  ->comment('Заметки менеджера при обработке заказа');
            
            $table->text('admin_notes')
                  ->nullable()
                  ->after('processing_notes')
                  ->comment('Внутренние заметки администратора');
            
            // Индекс для быстрого поиска заказов на обработку
            $table->index(['status', 'processed_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex(['status', 'processed_by']);
            
            // Удаляем новые поля
            $table->dropColumn([
                'processed_by',
                'processed_at',
                'processing_notes',
                'admin_notes'
            ]);
        });
        
        // Возвращаем старое enum (только основные статусы)
        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending', 'completed', 'failed') DEFAULT 'completed'");
        
        // Обновляем статусы processing и cancelled на completed
        DB::statement("UPDATE purchases SET status = 'completed' WHERE status IN ('processing', 'cancelled')");
    }
};
