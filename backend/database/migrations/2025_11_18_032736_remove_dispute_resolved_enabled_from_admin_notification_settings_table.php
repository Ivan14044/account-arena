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
            if (Schema::hasColumn('admin_notification_settings', 'dispute_resolved_enabled')) {
                $table->dropColumn('dispute_resolved_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_notification_settings', 'dispute_resolved_enabled')) {
                $table->boolean('dispute_resolved_enabled')->default(true)->after('dispute_created_enabled');
            }
        });
    }
};
