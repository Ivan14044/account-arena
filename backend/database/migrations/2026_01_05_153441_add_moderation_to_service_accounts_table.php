<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            // Добавляем поле статуса модерации
            $table->enum('moderation_status', ['pending', 'approved', 'rejected'])
                ->default('approved')
                ->after('is_active')
                ->comment('Статус модерации товара');
            
            // Комментарий администратора при отклонении
            $table->text('moderation_comment')->nullable()->after('moderation_status');
            
            // Дата и время модерации
            $table->timestamp('moderated_at')->nullable()->after('moderation_comment');
            
            // ID администратора, который провел модерацию
            $table->unsignedBigInteger('moderated_by')->nullable()->after('moderated_at');
            $table->foreign('moderated_by')->references('id')->on('users')->onDelete('set null');
            
            // Индекс для быстрого поиска товаров на модерации
            $table->index('moderation_status');
        });
        
        // ВАЖНО: Обновляем существующие товары
        // Товары поставщиков автоматически одобряем (для обратной совместимости)
        DB::table('service_accounts')
            ->whereNotNull('supplier_id')
            ->update(['moderation_status' => 'approved']);
        
        // Товары администраторов не требуют модерации, но устанавливаем статус approved
        DB::table('service_accounts')
            ->whereNull('supplier_id')
            ->update(['moderation_status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropIndex(['moderation_status']);
            $table->dropColumn([
                'moderation_status',
                'moderation_comment',
                'moderated_at',
                'moderated_by',
            ]);
        });
    }
};
