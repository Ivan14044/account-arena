<?php

namespace App\Observers;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;

class ArticleObserver
{
    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        $this->clearCache($article->id);
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        $this->clearCache($article->id);
    }

    /**
     * Clear article caches
     */
    protected function clearCache(?int $articleId = null): void
    {
        // Очищаем все списки статей (так как мы не знаем точно какие лимиты/оффсеты были закэшированы)
        // В продакшене лучше использовать тегирование кэша, если драйвер поддерживает (redis/memcached)
        // Но для универсальности очищаем по паттерну, если это возможно, или просто ждем истечения TTL.
        // Laravel не поддерживает очистку по паттерну для всех драйверов.
        
        // Самый надежный способ без тегов - это использование тегов кэша, но если они не настроены,
        // мы можем просто использовать фиксированный префикс и надеяться на короткий TTL или 
        // очистить конкретную статью.
        
        if ($articleId) {
            Cache::forget("article_show_{$articleId}");
        }
        
        // Для списков статей мы не можем легко перечислить все ключи.
        // Если проект использует Redis, можно было бы сделать Cache::tags(['articles'])->flush().
        // Для простоты и надежности в текущей архитектуре, мы полагаемся на то, 
        // что админ не часто правит статьи, и используем теги если они доступны.
        
        try {
            Cache::tags(['articles'])->flush();
        } catch (\BadMethodCallException $e) {
            // Драйвер не поддерживает теги (например, file или database)
            // В этом случае списки обновятся сами по истечении TTL (1 час)
        }
    }
}
