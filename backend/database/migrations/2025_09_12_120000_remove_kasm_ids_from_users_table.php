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
        $columnsToDrop = [];
        if (Schema::hasColumn('users', 'kasm_user_id')) {
            $columnsToDrop[] = 'kasm_user_id';
        }
        if (Schema::hasColumn('users', 'kasm_session_id')) {
            $columnsToDrop[] = 'kasm_session_id';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kasm_session_id')) {
                $table->string('kasm_session_id')->after('lang')->nullable();
            }
            if (!Schema::hasColumn('users', 'kasm_user_id')) {
                $table->string('kasm_user_id')->after('kasm_session_id')->nullable();
            }
        });
    }
};


