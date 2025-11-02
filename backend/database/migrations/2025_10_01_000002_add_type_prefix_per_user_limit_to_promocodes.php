<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promocodes', function (Blueprint $table) {
            $table->string('type')->default('discount')->after('code'); // discount | free_access
            $table->string('prefix')->nullable()->after('type');
            $table->unsignedInteger('per_user_limit')->default(1)->after('usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropColumn(['type', 'prefix', 'per_user_limit']);
        });
    }
};



