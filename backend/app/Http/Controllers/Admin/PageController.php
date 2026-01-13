<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy('id', 'desc')->get();

        $statistics = [
            'total' => $pages->count(),
            'active' => $pages->where('is_active', true)->count(),
            'inactive' => $pages->where('is_active', false)->count(),
        ];

        return view('admin.pages.index', compact('pages', 'statistics'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        if ($request->filled('slug')) {
            $slug = preg_replace('/[^A-Za-z0-9\/\-]+/', '-', $request->input('slug'));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '/-');
            $request->merge(['slug' => $slug]);
        }

        $validated = $request->validate(
            $this->getRules(),
            [],
            getTransAttributes(['title', 'content'])
        );

        $page = Page::create($validated);

        // Sanitize content before saving translations
        if (isset($validated['content']) && is_array($validated['content'])) {
            foreach ($validated['content'] as $lang => $content) {
                $validated['content'][$lang] = $this->sanitizeHtml($content);
            }
        }

        $page->saveTranslation($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page successfully created.');
    }

    public function edit(Page $page)
    {
        $page->load('translations');
        $pageData = $page->translations->groupBy('locale')->map(function ($translations) {
            return $translations->pluck('value', 'code')->toArray();
        });

        return view('admin.pages.edit', compact('page', 'pageData'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate($this->getRules($page->id));

        $page->update($validated);

        // Sanitize content before saving translations
        if (isset($validated['content']) && is_array($validated['content'])) {
            foreach ($validated['content'] as $lang => $content) {
                $validated['content'][$lang] = $this->sanitizeHtml($content);
            }
        }

        $page->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.pages.edit', $page->id)
            : route('admin.pages.index');

        return redirect($route)->with('success', 'Page successfully updated.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page successfully deleted.');
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
            'name' => 'required|string|max:255',
            'slug' => ['required', 'unique:pages' . ($id ? ',slug,' . $id : null)],
            'is_active' => 'required|boolean',
        ];

        foreach (config('langs') as $lang => $flag) {
            foreach(Page::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
