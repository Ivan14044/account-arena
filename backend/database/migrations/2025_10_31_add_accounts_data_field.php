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
        // For SQLite, recreate table
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('service_accounts');
            Schema::create('service_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('profile_id')->nullable();
                $table->json('credentials')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('used')->default(0);
                $table->timestamp('expiring_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->text('title_en')->nullable();
                $table->text('description_en')->nullable();
                $table->text('title_uk')->nullable();
                $table->text('description_uk')->nullable();
                $table->string('image_url')->nullable();
                $table->text('additional_description')->nullable();
                $table->text('additional_description_en')->nullable();
                $table->text('additional_description_uk')->nullable();
                $table->text('meta_title')->nullable();
                $table->text('meta_title_en')->nullable();
                $table->text('meta_title_uk')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('meta_description_en')->nullable();
                $table->text('meta_description_uk')->nullable();
                $table->boolean('show_only_telegram')->default(false);
                $table->json('accounts_data')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('service_accounts', function (Blueprint $table) {
                // Add accounts_data after credentials if credentials exists, otherwise after id
                if (Schema::hasColumn('service_accounts', 'credentials')) {
                    $table->json('accounts_data')->nullable()->after('credentials');
                } else {
                    $table->json('accounts_data')->nullable();
                }
                
                // Add title_uk after title_en if exists, otherwise after title
                if (Schema::hasColumn('service_accounts', 'title_en')) {
                    $table->text('title_uk')->nullable()->after('title_en');
                } elseif (Schema::hasColumn('service_accounts', 'title')) {
                    $table->text('title_uk')->nullable()->after('title');
                } else {
                    $table->text('title_uk')->nullable();
                }
                
                // Add description_uk after description_en if exists, otherwise after description
                if (Schema::hasColumn('service_accounts', 'description_en')) {
                    $table->text('description_uk')->nullable()->after('description_en');
                } elseif (Schema::hasColumn('service_accounts', 'description')) {
                    $table->text('description_uk')->nullable()->after('description');
                } else {
                    $table->text('description_uk')->nullable();
                }
                
                // Add additional_description_en after additional_description if exists
                if (Schema::hasColumn('service_accounts', 'additional_description')) {
                    $table->text('additional_description_en')->nullable()->after('additional_description');
                } else {
                    $table->text('additional_description_en')->nullable();
                }
                
                // Add additional_description_uk after additional_description_en if exists
                if (Schema::hasColumn('service_accounts', 'additional_description_en')) {
                    $table->text('additional_description_uk')->nullable()->after('additional_description_en');
                } else {
                    $table->text('additional_description_uk')->nullable();
                }
                
                // Add meta_title_en after meta_title if exists
                if (Schema::hasColumn('service_accounts', 'meta_title')) {
                    $table->text('meta_title_en')->nullable()->after('meta_title');
                } else {
                    $table->text('meta_title_en')->nullable();
                }
                
                // Add meta_title_uk after meta_title_en if exists
                if (Schema::hasColumn('service_accounts', 'meta_title_en')) {
                    $table->text('meta_title_uk')->nullable()->after('meta_title_en');
                } else {
                    $table->text('meta_title_uk')->nullable();
                }
                
                // Add meta_description_en after meta_description if exists
                if (Schema::hasColumn('service_accounts', 'meta_description')) {
                    $table->text('meta_description_en')->nullable()->after('meta_description');
                } else {
                    $table->text('meta_description_en')->nullable();
                }
                
                // Add meta_description_uk after meta_description_en if exists
                if (Schema::hasColumn('service_accounts', 'meta_description_en')) {
                    $table->text('meta_description_uk')->nullable()->after('meta_description_en');
                } else {
                    $table->text('meta_description_uk')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('service_accounts', function (Blueprint $table) {
                $table->dropColumn([
                    'accounts_data',
                    'title_uk',
                    'description_uk',
                    'additional_description_en',
                    'additional_description_uk',
                    'meta_title_en',
                    'meta_title_uk',
                    'meta_description_en',
                    'meta_description_uk',
                ]);
            });
        }
    }
};

