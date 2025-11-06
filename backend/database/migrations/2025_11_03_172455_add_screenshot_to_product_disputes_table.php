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
        Schema::table('product_disputes', function (Blueprint $table) {
            $table->string('screenshot_url', 500)->nullable()->after('customer_description');
            $table->string('screenshot_type', 20)->nullable()->after('screenshot_url'); // 'upload' or 'link'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_disputes', function (Blueprint $table) {
            $table->dropColumn(['screenshot_url', 'screenshot_type']);
        });
    }
};
