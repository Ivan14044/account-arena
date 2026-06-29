<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleTranslation;
use Database\Seeders\ArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регресс для демо-блока «Полезные статьи»: сидер должен создавать
 * опубликованные статьи с переводами на ru/uk/en, чтобы блок на главной
 * (ArticleSection рендерится только при articles.length > 0) был непустым.
 * Также проверяем идемпотентность — повторный прогон не плодит дубли.
 */
class ArticlesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_published_articles_with_translations(): void
    {
        $this->seed(ArticlesSeeder::class);

        $articles = Article::all();
        $this->assertCount(6, $articles, 'Ожидаем 6 демо-статей');

        // Все опубликованы — иначе ArticleController (where status=published) их не отдаст
        $this->assertSame(6, Article::where('status', 'published')->count());

        foreach ($articles as $article) {
            // у каждой статьи есть заголовок на всех трёх локалях
            foreach (['ru', 'uk', 'en'] as $locale) {
                $title = ArticleTranslation::where('article_id', $article->id)
                    ->where('locale', $locale)
                    ->where('code', 'title')
                    ->value('value');
                $this->assertNotEmpty($title, "Нет заголовка ({$locale}) у статьи #{$article->id}");
            }
            $this->assertNotEmpty($article->img, 'У статьи должна быть обложка');
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ArticlesSeeder::class);
        $this->seed(ArticlesSeeder::class);

        $this->assertSame(6, Article::count(), 'Повторный сид не должен создавать дубли');
    }
}
