<?php

namespace App\Support\Seo;

/**
 * Stateless SEO text helpers shared by SpaController (meta-tag resolution) and
 * SsrContentRenderer (SSR body rendering). Extracted verbatim from SpaController.
 */
class SeoText
{
    /**
     * Resolve a model's localized field: `field` for `ru`, otherwise `field_<locale>`
     * with a fallback to the base `field`.
     */
    public static function localized($model, string $field, string $locale): ?string
    {
        $localizedField = ($locale === 'ru') ? $field : $field . '_' . $locale;
        return $model->$localizedField ?: $model->$field;
    }

    /**
     * Smart truncation that respects word boundaries.
     */
    public static function truncate(string $text, int $limit = 160): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $text = mb_substr($text, 0, $limit);
        // Find last space to avoid cutting words
        $lastSpace = mb_strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }

        return $text . '...';
    }
}
