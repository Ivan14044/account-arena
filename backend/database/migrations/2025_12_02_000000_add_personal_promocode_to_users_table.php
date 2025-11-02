<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('personal_discount')->default(0)->after('provider');
            $table->timestamp('personal_discount_expires_at')->nullable()->after('personal_discount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['personal_discount', 'personal_discount_expires_at']);
        });
    }
};

