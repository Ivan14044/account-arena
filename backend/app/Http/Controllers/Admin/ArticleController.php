<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::withRussianTitle()
            ->with(['categories.translations'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        $statistics = [
            'total' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'draft' => Article::where('status', 'draft')->count(),
        ];

        return view('admin.articles.index', compact('articles', 'statistics'));
    }

    public function create()
    {
        $categories = Category::articleCategories()->with('translations')->get();
        return view('admin.articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['title', 'content', 'meta_title', 'meta_description', 'short'])
        );

        $path = false;
        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('articles', 'public');
        }

        $article = Article::create([
            'status' => $validated['is_active'] ? 'published' : 'draft',
            'img' => $path ? Storage::url($path) : null
        ]);

        $article->categories()->sync($validated['categories'] ?? []);

        // Sanitize content and short description before saving translations
        foreach (['content', 'short'] as $field) {
            if (isset($validated[$field]) && is_array($validated[$field])) {
                foreach ($validated[$field] as $lang => $content) {
                    $validated[$field][$lang] = $this->sanitizeHtml($content);
                }
            }
        }

        $article->saveTranslation($validated);

        return redirect()->route('admin.articles.index')->with('success', 'Article successfully created.');
    }


    public function edit(Article $article)
    {
        $categories = Category::articleCategories()->with('translations')->get();
        $article->load(['categories', 'translations']);

        $articleData = $article->translations
            ->groupBy('locale')
            ->map(function ($translations) {
                return [
                    'title' => optional($translations->firstWhere('code', 'title'))->value,
                    'content' => optional($translations->firstWhere('code', 'content'))->value,
                    'short' => optional($translations->firstWhere('code', 'short'))->value,
                    'meta_title' => optional($translations->firstWhere('code', 'meta_title'))->value,
                    'meta_description' => optional($translations->firstWhere('code', 'meta_description'))->value,
                ];
            });
        return view('admin.articles.edit', compact('article', 'categories', 'articleData'));
    }

    public function update(Request $request, Article $article)
    {
        $article->load('translations', 'categories');

        $validated = $request->validate(
            $this->getRules($article->id),
            [],
            getTransAttributes(['title', 'content', 'meta_title', 'meta_description', 'short'])
        );

        $path = false;
        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('articles', 'public');
        }

        $article->update([
            'status' => $validated['is_active'] ? 'published' : 'draft',
            'img' => $path ? Storage::url($path) : ($request->img_text ?? null),
        ]);

        $article->categories()->sync($validated['categories'] ?? []);

        // Sanitize content and short description before saving translations
        foreach (['content', 'short'] as $field) {
            if (isset($validated[$field]) && is_array($validated[$field])) {
                foreach ($validated[$field] as $lang => $content) {
                    $validated[$field][$lang] = $this->sanitizeHtml($content);
                }
            }
        }

        $article->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.articles.edit', $article->id)
            : route('admin.articles.index');

        return redirect($route)->with('success', 'Article successfully updated.');
    }

    public function destroy(Article $article)
    {
        $article->categories()->detach();
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Article successfully deleted.');
    }

    /**
     * Sanitize HTML content to prevent XSS while keeping safe tags.
     */
    private function sanitizeHtml($html)
    {
        if (empty($html)) return $html;

        // Список разрешенных тегов (для CKEditor)
        $allowedTags = '<p><a><b><i><u><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6><img><blockquote><pre><code><table><thead><tbody><tr><th><td><hr>';
        
        // 1. Сначала используем strip_tags с белым списком
        $sanitized = strip_tags($html, $allowedTags);
        
        // 2. Дополнительная очистка от опасных атрибутов (onmouseover, onclick, javascript: и т.д.)
        $sanitized = preg_replace('/on\w+\s*=\s*".*?"/i', '', $sanitized);
        $sanitized = preg_replace('/on\w+\s*=\s*\'.*?\'/i', '', $sanitized);
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized);
        
        return $sanitized;
    }

    private function getRules($id = false)
    {
        $rules = [
            'is_active' => ['required', 'boolean'],
            'img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:10240'],
            'img_text' => ['nullable', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ];

        foreach (config('langs') as $lang => $flag) {
            foreach (Article::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
