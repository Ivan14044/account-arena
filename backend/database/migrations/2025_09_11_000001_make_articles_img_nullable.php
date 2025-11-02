<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support MODIFY, so we need to detect database type
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support modifying columns, so we just skip this
            // The table was created with img as nullable in the initial migration
        } else {
            DB::statement('ALTER TABLE `articles` MODIFY `img` VARCHAR(255) NULL DEFAULT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // Skip for SQLite
        } else {
            DB::statement('ALTER TABLE `articles` MODIFY `img` VARCHAR(255) NOT NULL');
        }
    }
};




