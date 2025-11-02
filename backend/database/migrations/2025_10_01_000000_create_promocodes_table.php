<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            // Percent discount 0..100
            $table->unsignedTinyInteger('percent_discount')->default(0);
            // Total usage limit (0 means unlimited)
            $table->unsignedInteger('usage_limit')->default(0);
            // How many times used in total
            $table->unsignedInteger('usage_count')->default(0);
            // Validity period
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // Is active toggle to quickly disable
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocodes');
    }
};



