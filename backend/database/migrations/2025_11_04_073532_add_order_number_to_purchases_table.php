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
        Schema::table('purchases', function (Blueprint $table) {
            // Добавляем поле для номера заказа (nullable сначала)
            $table->string('order_number', 50)->nullable()->after('id');
        });
        
        // Заполняем существующие записи номерами заказов
        \DB::table('purchases')->orderBy('id')->chunk(100, function ($purchases) {
            foreach ($purchases as $purchase) {
                // Генерируем номер заказа для существующих записей
                do {
                    $orderNumber = 'ORD-' . date('Ymd', strtotime($purchase->created_at)) . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $exists = \DB::table('purchases')->where('order_number', $orderNumber)->exists();
                } while ($exists);
                
                \DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update(['order_number' => $orderNumber]);
            }
        });
        
        // Теперь делаем поле уникальным и добавляем индекс
        Schema::table('purchases', function (Blueprint $table) {
            $table->unique('order_number');
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['order_number']);
            $table->dropColumn('order_number');
        });
    }
};
