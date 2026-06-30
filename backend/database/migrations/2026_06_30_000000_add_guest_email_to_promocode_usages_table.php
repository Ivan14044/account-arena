<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promocode_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('promocode_usages', 'guest_email')) {
                // Привязка гостевого использования промокода к email
                // для per-guest лимита (у гостей нет user_id).
                $table->string('guest_email')->nullable()->index()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promocode_usages', function (Blueprint $table) {
            if (Schema::hasColumn('promocode_usages', 'guest_email')) {
                $table->dropColumn('guest_email');
            }
        });
    }
};
