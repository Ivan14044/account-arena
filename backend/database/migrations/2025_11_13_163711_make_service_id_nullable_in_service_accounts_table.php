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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite не поддерживает MODIFY, пропускаем
            return;
        }

        $constraintName = $this->getForeignKeyName();

        if ($constraintName) {
            DB::statement("ALTER TABLE `service_accounts` DROP FOREIGN KEY `{$constraintName}`");
        } else {
            try {
                Schema::table('service_accounts', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                });
            } catch (\Exception $e) {
                try {
                    DB::statement('ALTER TABLE `service_accounts` DROP FOREIGN KEY `service_accounts_service_id_foreign`');
                } catch (\Exception $e2) {
                    // ignore
                }
            }
        }

        DB::statement('ALTER TABLE `service_accounts` MODIFY `service_id` BIGINT UNSIGNED NULL');

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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $constraintName = $this->getForeignKeyName();

        if ($constraintName) {
            DB::statement("ALTER TABLE `service_accounts` DROP FOREIGN KEY `{$constraintName}`");
        } else {
            try {
                Schema::table('service_accounts', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                });
            } catch (\Exception $e) {
                try {
                    DB::statement('ALTER TABLE `service_accounts` DROP FOREIGN KEY `service_accounts_service_id_foreign`');
                } catch (\Exception $e2) {
                    // ignore
                }
            }
        }

        DB::statement('ALTER TABLE `service_accounts` MODIFY `service_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('service_accounts', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }
};
