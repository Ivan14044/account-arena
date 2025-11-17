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
        Schema::table('product_disputes', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['supplier_id']);
            
            // Make the column nullable
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
            
            // Re-add the foreign key constraint with onDelete('set null')
            $table->foreign('supplier_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_disputes', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['supplier_id']);
            
            // Make the column not nullable again
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with onDelete('cascade')
            $table->foreign('supplier_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
