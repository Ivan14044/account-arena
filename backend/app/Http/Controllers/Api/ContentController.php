<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;

class ContentController extends Controller
{
    public function show(string $code)
    {
        $content = Content::where('code', $code)->with('translations')->firstOrFail();

        $allFields = array_keys(config("contents.{$code}.fields", []));

        $grouped = $content->translations
            ->filter(fn($t) => str_starts_with($t->code, $code . '.'))
            ->groupBy('locale')
            ->map(function ($translations) use ($code, $allFields) {
                $entries = [];

                foreach ($translations as $t) {
                    $parts = explode('.', $t->code);
                    if (count($parts) !== 3) continue;

                    [, $field, $index] = $parts;
                    $entries[$index][$field] = $t->value;
                }

                foreach ($entries as &$entry) {
                    foreach ($allFields as $field) {
                        if (!array_key_exists($field, $entry)) {
                            $entry[$field] = null;
                        }
                    }
                }

                return array_values($entries);
            });

        return response()->json([
            'code' => $code,
            'items' => $grouped,
        ]);
    }
}
