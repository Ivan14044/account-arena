<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('promocode_usages', 'used_at')) {
            Schema::table('promocode_usages', function (Blueprint $table) {
                $table->dropColumn('used_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('promocode_usages', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable();
        });
    }
};


