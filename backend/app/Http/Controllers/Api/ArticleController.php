<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $limit = min((int) $request->input('limit', 10), 100);
        $offset = max((int) $request->input('offset', 0), 0);
        $categoryId = $request->input('category_id');

        $query = Article::with(['translations', 'categories.translations'])
            ->where('status', 'published')
            ->orderByDesc('id');

        if (filter_var($categoryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', (int) $categoryId));
        }

        $total = (clone $query)->count();

        $articles = $query->offset($offset)->limit($limit)->get();

        $items = $articles->map(function ($article) {
            $item = $article->toArray();

            $item['translations'] = $article->translations
                ->groupBy('locale')
                ->map(fn($t) => $t->pluck('value', 'code')->toArray())
                ->toArray();

            $item['categories'] = $article->categories
                ->map(function ($cat) {
                    $c = $cat->toArray();

                    $c['translations'] = $cat->translations
                        ->groupBy('locale')
                        ->map(fn($t) => $t->pluck('value', 'code')->toArray())
                        ->toArray();

                    return $c;
                })
                ->values()
                ->toArray();

            return $item;
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'total' => $total,
            'items' => $items,
        ]);
    }

    public function show(Article $article)
    {
        if ($article->status !== 'published') {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $article->loadMissing(['translations', 'categories']);

        $data = $article->toArray();
        $data['translations'] = $article->translations
            ->groupBy('locale')
            ->map(fn($translations) => $translations->pluck('value', 'code'));

        $data['categories'] = $article->categories
            ->map(function ($cat) {
                $c = $cat->toArray();

                $c['translations'] = $cat->translations
                    ->groupBy('locale')
                    ->map(fn ($t) => $t->pluck('value', 'code'))
                    ->toArray();

                unset($c['pivot']);

                return $c;
            })
            ->values()
            ->toArray();

        return response()->json($data);
    }
}
