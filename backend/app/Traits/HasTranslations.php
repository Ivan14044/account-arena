<?php

namespace App\Traits;

trait HasTranslations
{
    public function saveTranslation(array $validated): void
    {
        $this->translations()->delete();

        foreach (self::TRANSLATION_FIELDS as $code) {
            foreach ($validated[$code] ?? [] as $locale => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $this->translations()->updateOrCreate(
                    ['locale' => $locale, 'code' => $code],
                    ['value' => $value]
                );
            }
        }
    }
}
