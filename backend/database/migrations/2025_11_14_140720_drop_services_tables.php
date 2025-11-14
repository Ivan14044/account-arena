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
        // Drop foreign keys first
        // Drop foreign key from service_accounts if exists
        if (Schema::hasTable('service_accounts')) {
            try {
                Schema::table('service_accounts', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                });
            } catch (\Exception $e) {
                // Try to drop using raw SQL if Laravel method fails
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
                    
                    if (!empty($constraints)) {
                        DB::statement("ALTER TABLE `service_accounts` DROP FOREIGN KEY `{$constraints[0]->CONSTRAINT_NAME}`");
                    }
                } catch (\Exception $e2) {
                    // Foreign key might not exist, continue
                }
            }
        }

        // Drop foreign key from subscriptions if exists
        if (Schema::hasTable('subscriptions')) {
            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                });
            } catch (\Exception $e) {
                // Try to drop using raw SQL if Laravel method fails
                try {
                    $constraints = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'subscriptions' 
                        AND COLUMN_NAME = 'service_id' 
                        AND CONSTRAINT_NAME != 'PRIMARY'
                        LIMIT 1
                    ");
                    
                    if (!empty($constraints)) {
                        DB::statement("ALTER TABLE `subscriptions` DROP FOREIGN KEY `{$constraints[0]->CONSTRAINT_NAME}`");
                    }
                } catch (\Exception $e2) {
                    // Foreign key might not exist, continue
                }
            }
        }

        // Drop intermediate table for promocode-service relationship
        Schema::dropIfExists('promocode_service');

        // Drop service translations table
        Schema::dropIfExists('service_translations');

        // Drop services table
        Schema::dropIfExists('services');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate services table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('logo');
            $table->integer('position');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate service_translations table
        Schema::create('service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('locale');
            $table->string('code');
            $table->text('value');
            $table->timestamps();
        });

        // Recreate promocode_service table
        Schema::create('promocode_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocode_id')->constrained('promocodes')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->unsignedInteger('free_days')->default(0);
            $table->timestamps();
            $table->unique(['promocode_id', 'service_id']);
        });

        // Re-add foreign keys if tables exist
        if (Schema::hasTable('service_accounts') && Schema::hasColumn('service_accounts', 'service_id')) {
            Schema::table('service_accounts', function (Blueprint $table) {
                $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'service_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreign('service_id')
                    ->references('id')
                    ->on('services')
                    ->cascadeOnDelete();
            });
        }
    }
};
