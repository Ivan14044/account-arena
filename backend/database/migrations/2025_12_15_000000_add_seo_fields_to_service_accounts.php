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
            // SEO текст (300-500 слов)
            if (!Schema::hasColumn('service_accounts', 'seo_text')) {
                $table->text('seo_text')->nullable()->after('meta_description_uk');
            }
            if (!Schema::hasColumn('service_accounts', 'seo_text_en')) {
                $table->text('seo_text_en')->nullable()->after('seo_text');
            }
            if (!Schema::hasColumn('service_accounts', 'seo_text_uk')) {
                $table->text('seo_text_uk')->nullable()->after('seo_text_en');
            }
            
            // Инструкция по использованию
            if (!Schema::hasColumn('service_accounts', 'instruction')) {
                $table->text('instruction')->nullable()->after('seo_text_uk');
            }
            if (!Schema::hasColumn('service_accounts', 'instruction_en')) {
                $table->text('instruction_en')->nullable()->after('instruction');
            }
            if (!Schema::hasColumn('service_accounts', 'instruction_uk')) {
                $table->text('instruction_uk')->nullable()->after('instruction_en');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'seo_text',
                'seo_text_en',
                'seo_text_uk',
                'instruction',
                'instruction_en',
                'instruction_uk',
            ]);
        });
    }
};
