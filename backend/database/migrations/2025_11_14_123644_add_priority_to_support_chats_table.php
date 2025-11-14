<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropColumn('priority');
        });
    }
};