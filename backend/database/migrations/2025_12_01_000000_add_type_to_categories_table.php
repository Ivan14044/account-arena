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
        // For SQLite, we need to recreate the table
        if (DB::getDriverName() === 'sqlite') {
            // Get current structure and data
            $categories = DB::table('categories')->get();
            $translations = DB::table('category_translations')->get();
            $articleCategories = DB::table('article_category')->get();

            // Drop all category-related tables
            Schema::dropIfExists('article_category');
            Schema::dropIfExists('category_translations');
            Schema::dropIfExists('categories');

            // Recreate categories table with type field
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('type')->default('article'); // 'product' or 'article'
                $table->timestamps();
            });

            // Recreate category_translations
            Schema::create('category_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id');
                $table->string('locale');
                $table->string('code');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                $table->unique(['category_id', 'locale', 'code']);
            });

            // Recreate article_category
            Schema::create('article_category', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('article_id');
                $table->unsignedBigInteger('category_id');
                $table->timestamps();

                $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                $table->unique(['article_id', 'category_id']);
            });

            // Restore data
            foreach ($categories as $category) {
                DB::table('categories')->insert([
                    'id' => $category->id,
                    'type' => 'article', // All existing categories are article categories
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ]);
            }

            foreach ($translations as $translation) {
                DB::table('category_translations')->insert([
                    'id' => $translation->id,
                    'category_id' => $translation->category_id,
                    'locale' => $translation->locale,
                    'code' => $translation->code,
                    'value' => $translation->value,
                    'created_at' => $translation->created_at,
                    'updated_at' => $translation->updated_at,
                ]);
            }

            foreach ($articleCategories as $articleCategory) {
                DB::table('article_category')->insert([
                    'id' => $articleCategory->id,
                    'article_id' => $articleCategory->article_id,
                    'category_id' => $articleCategory->category_id,
                    'created_at' => $articleCategory->created_at,
                    'updated_at' => $articleCategory->updated_at,
                ]);
            }
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('type')->default('article')->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};

