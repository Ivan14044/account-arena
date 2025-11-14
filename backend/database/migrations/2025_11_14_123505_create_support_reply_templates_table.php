<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_reply_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название шаблона
            $table->text('content'); // Содержание шаблона
            $table->integer('usage_count')->default(0); // Счетчик использования
            $table->integer('order')->default(0); // Порядок сортировки
            $table->boolean('is_active')->default(true); // Активен ли шаблон
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_reply_templates');
    }
};