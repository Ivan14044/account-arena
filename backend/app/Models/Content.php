<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_systems',
    ];

    public function translations()
    {
        return $this->hasMany(ContentTranslation::class);
    }

    public function saveTranslation(array $validated, string $prefix): void
    {
        $this->translations()
            ->where('code', 'like', $prefix . '.%')
            ->delete();

        if (!isset($validated['fields']) || !is_array($validated['fields'])) {
            return;
        }

        foreach ($validated['fields'] as $fieldKey => $translationsByLocale) {
            foreach ($translationsByLocale as $locale => $values) {
                foreach ($values as $index => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $code = "{$prefix}.{$fieldKey}.{$index}";

                    $this->translations()->updateOrCreate(
                        ['locale' => $locale, 'code' => $code],
                        ['value' => $value ?? '']
                    );
                }
            }
        }
    }
}
