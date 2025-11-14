<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->tinyInteger('rating')->nullable()->after('priority'); // Оценка от 1 до 5
            $table->text('rating_comment')->nullable()->after('rating'); // Комментарий к оценке
            $table->timestamp('rated_at')->nullable()->after('rating_comment'); // Когда была поставлена оценка
        });
    }

    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_comment', 'rated_at']);
        });
    }
};