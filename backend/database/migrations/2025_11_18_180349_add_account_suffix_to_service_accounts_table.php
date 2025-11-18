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
            $table->boolean('account_suffix_enabled')->default(false)->after('accounts_data');
            $table->text('account_suffix_text_ru')->nullable()->after('account_suffix_enabled');
            $table->text('account_suffix_text_en')->nullable()->after('account_suffix_text_ru');
            $table->text('account_suffix_text_uk')->nullable()->after('account_suffix_text_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_suffix_enabled', 'account_suffix_text_ru', 'account_suffix_text_en', 'account_suffix_text_uk']);
        });
    }
};
