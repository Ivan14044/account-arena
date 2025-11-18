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
        Schema::create('admin_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Типы уведомлений (включены/выключены)
            $table->boolean('registration_enabled')->default(true);
            $table->boolean('product_purchase_enabled')->default(true);
            $table->boolean('dispute_created_enabled')->default(true);
            $table->boolean('payment_enabled')->default(true);
            $table->boolean('topup_enabled')->default(true);
            $table->boolean('support_chat_enabled')->default(true);
            
            // Звуковое оповещение
            $table->boolean('sound_enabled')->default(true);
            
            $table->timestamps();
            
            // Уникальный индекс - один админ = одна запись настроек
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notification_settings');
    }
};
