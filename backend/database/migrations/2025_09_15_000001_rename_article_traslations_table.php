<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('article_traslations') && !Schema::hasTable('article_translations')) {
            Schema::rename('article_traslations', 'article_translations');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('article_translations') && !Schema::hasTable('article_traslations')) {
            Schema::rename('article_translations', 'article_traslations');
        }
    }
};


