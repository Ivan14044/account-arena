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
        Schema::table('admin_notification_settings', function (Blueprint $table) {
            $table->boolean('support_chat_enabled')->default(true)->after('topup_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notification_settings', function (Blueprint $table) {
            $table->dropColumn('support_chat_enabled');
        });
    }
};
