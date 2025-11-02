<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $contents = Content::query()
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.contents.index', compact('contents'));
    }

    public function create()
    {
        return view('admin.contents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());

        $content = Content::create($validated);

        return redirect()->route('admin.contents.index')->with('success', 'Content successfully created.');
    }

    public function edit(Content $content)
    {
        $content->load('translations');
        $services = Service::with('translations')->get();
        $contentData = $content->translations->groupBy('locale')->map(function ($translations) use ($content) {
            $entries = [];

            foreach ($translations as $t) {
                if (!str_starts_with($t->code, $content->code . '.')) {
                    continue;
                }

                $parts = explode('.', $t->code);
                if (count($parts) === 3) {
                    [, $field, $index] = $parts;
                    $entries[$index][$field] = $t->value;
                }
            }

            return $entries ?: [[]];
        });

        return view('admin.contents.edit', compact('content', 'contentData', 'services'));
    }

    public function update(Request $request, Content $content)
    {
        $validated = $request->validate($this->getRules($content->id));

        // Handle uploaded files for dynamic fields
        $uploadedFields = $request->file('fields_file', []);
        foreach ($uploadedFields as $fieldKey => $locales) {
            foreach ($locales as $locale => $filesByIndex) {
                foreach ($filesByIndex as $index => $uploadedFile) {
                    if ($uploadedFile) {
                        $path = $uploadedFile->store('contents', 'public');
                        $url = Storage::url($path);
                        $validated['fields'][$fieldKey][$locale][$index] = $url;
                    }
                }
            }
        }

        $content->update($validated);
        $content->saveTranslation($validated, $content->code);

        $route = $request->has('save')
            ? route('admin.contents.edit', $content->id)
            : route('admin.contents.index');

        return redirect($route)->with('success', 'Content successfully updated.');
    }

    public function destroy(Content $content)
    {
        $content->delete();

        return redirect()->route('admin.contents.index', ['type' => 'custom'])
            ->with('success', 'Content template successfully deleted.');
    }

    private function getRules($id = false)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => ['required', 'unique:contents' . ($id ? ',code,' . $id : null)],
            'fields' => 'nullable|array',
            'fields_file' => 'nullable|array',
            'fields_file.*' => 'nullable|array',
            'fields_file.*.*' => 'nullable|array',
            'fields_file.*.*.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:10240',
        ];

        return $rules;
    }
}
