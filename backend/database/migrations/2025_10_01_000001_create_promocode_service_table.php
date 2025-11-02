<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promocode_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocode_id')->constrained('promocodes')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            // Free access duration in days for this service (0 means no free access)
            $table->unsignedInteger('free_days')->default(0);
            $table->timestamps();

            $table->unique(['promocode_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocode_service');
    }
};



