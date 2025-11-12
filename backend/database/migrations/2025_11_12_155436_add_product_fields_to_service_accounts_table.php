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
        Schema::table('service_accounts', function (Blueprint $table) {
            // Add category_id if not exists
            if (!Schema::hasColumn('service_accounts', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('service_id');
            }
            
            // Add price if not exists
            if (!Schema::hasColumn('service_accounts', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('last_used_at');
            }
            
            // Add title if not exists
            if (!Schema::hasColumn('service_accounts', 'title')) {
                $table->string('title')->nullable()->after('price');
            }
            
            // Add description if not exists
            if (!Schema::hasColumn('service_accounts', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            
            // Add title_en if not exists
            if (!Schema::hasColumn('service_accounts', 'title_en')) {
                $table->text('title_en')->nullable()->after('description');
            }
            
            // Add description_en if not exists
            if (!Schema::hasColumn('service_accounts', 'description_en')) {
                $table->text('description_en')->nullable()->after('title_en');
            }
            
            // Add image_url if not exists
            if (!Schema::hasColumn('service_accounts', 'image_url')) {
                $table->string('image_url')->nullable()->after('description_en');
            }
            
            // Add additional_description if not exists
            if (!Schema::hasColumn('service_accounts', 'additional_description')) {
                $table->text('additional_description')->nullable()->after('image_url');
            }
            
            // Add meta_title if not exists
            if (!Schema::hasColumn('service_accounts', 'meta_title')) {
                $table->text('meta_title')->nullable()->after('additional_description');
            }
            
            // Add meta_description if not exists
            if (!Schema::hasColumn('service_accounts', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            
            // Add show_only_telegram if not exists
            if (!Schema::hasColumn('service_accounts', 'show_only_telegram')) {
                $table->boolean('show_only_telegram')->default(false)->after('meta_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $columns = [
                'category_id',
                'price',
                'title',
                'description',
                'title_en',
                'description_en',
                'image_url',
                'additional_description',
                'meta_title',
                'meta_description',
                'show_only_telegram',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('service_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
