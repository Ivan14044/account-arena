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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_supplier')->default(false)->after('is_admin');
            $table->decimal('supplier_balance', 10, 2)->default(0)->after('balance');
            $table->decimal('supplier_commission', 5, 2)->default(10)->after('supplier_balance')->comment('Commission percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_supplier', 'supplier_balance', 'supplier_commission']);
        });
    }
};
