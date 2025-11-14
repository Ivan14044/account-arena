<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get and drop foreign key using raw SQL
        $constraintName = $this->getForeignKeyName();
        
        if ($constraintName) {
            // Drop foreign key using raw SQL
            DB::statement("ALTER TABLE `service_accounts` DROP FOREIGN KEY `{$constraintName}`");
        } else {
            // Try to drop using Laravel's method (passes column name, not constraint name)
            try {
                Schema::table('service_accounts', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                });
            } catch (\Exception $e) {
                // If that fails, try common constraint name
                try {
                    DB::statement('ALTER TABLE `service_accounts` DROP FOREIGN KEY `service_accounts_service_id_foreign`');
                } catch (\Exception $e2) {
                    // Foreign key might not exist, continue
                }
            }
        }
        
        // Modify column to be nullable using raw SQL
        DB::statement('ALTER TABLE `service_accounts` MODIFY `service_id` BIGINT UNSIGNED NULL');
        
        // Re-add foreign key
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }
    
    /**
     * Get the foreign key constraint name
     */
    private function getForeignKeyName(): ?string
    {
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'service_accounts' 
                AND COLUMN_NAME = 'service_id' 
                AND CONSTRAINT_NAME != 'PRIMARY'
                LIMIT 1
            ");
            
            return !empty($constraints) ? $constraints[0]->CONSTRAINT_NAME : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get and drop foreign key using raw SQL
        $constraintName = $this->getForeignKeyName();
        
        if ($constraintName) {
            // Drop foreign key using raw SQL
            DB::statement("ALTER TABLE `service_accounts` DROP FOREIGN KEY `{$constraintName}`");
        } else {
            // Try to drop using Laravel's method
            try {
                Schema::table('service_accounts', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                });
            } catch (\Exception $e) {
                // If that fails, try common constraint name
                try {
                    DB::statement('ALTER TABLE `service_accounts` DROP FOREIGN KEY `service_accounts_service_id_foreign`');
                } catch (\Exception $e2) {
                    // Foreign key might not exist, continue
                }
            }
        }
        
        // Modify column to be NOT NULL using raw SQL
        // Note: Before making NOT NULL, ensure all NULL values are handled
        // DB::statement('UPDATE `service_accounts` SET `service_id` = 1 WHERE `service_id` IS NULL');
        
        DB::statement('ALTER TABLE `service_accounts` MODIFY `service_id` BIGINT UNSIGNED NOT NULL');
        
        // Re-add foreign key
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }
};
