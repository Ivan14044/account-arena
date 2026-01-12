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
            if (!Schema::hasColumn('admin_notification_settings', 'manual_delivery_enabled')) {
                $table->boolean('manual_delivery_enabled')->default(true)->after('support_chat_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_notification_settings', 'manual_delivery_enabled')) {
                $table->dropColumn('manual_delivery_enabled');
            }
        });
    }
};
