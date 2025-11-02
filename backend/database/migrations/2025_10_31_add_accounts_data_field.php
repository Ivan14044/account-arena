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
                $table->json('accounts_data')->nullable()->after('credentials');
                $table->text('title_uk')->nullable()->after('title_en');
                $table->text('description_uk')->nullable()->after('description_en');
                $table->text('additional_description_en')->nullable()->after('additional_description');
                $table->text('additional_description_uk')->nullable()->after('additional_description_en');
                $table->text('meta_title_en')->nullable()->after('meta_title');
                $table->text('meta_title_uk')->nullable()->after('meta_title_en');
                $table->text('meta_description_en')->nullable()->after('meta_description');
                $table->text('meta_description_uk')->nullable()->after('meta_description_en');
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

