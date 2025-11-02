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

        return view('admin.pages.index', compact('pages'));
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
