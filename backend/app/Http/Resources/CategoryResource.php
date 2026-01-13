<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        
        // Handle translations
        $translations = $this->translations
            ->groupBy('locale')
            ->map(function ($trans) {
                return $trans->pluck('value', 'code');
            });

        // Get localized name
        $name = $translations[$locale]['name'] ?? null;
        if (!$name && $translations->count() > 0) {
            $name = $translations[array_key_first($translations->toArray())]['name'] ?? null;
        }

        // Аксессор image_url в модели Category уже возвращает полный URL
        // Не нужно дополнительно обрабатывать его здесь

        $result = [
            'id' => $this->id,
            'type' => $this->type,
            'image_url' => $this->image_url, // Аксессор уже возвращает полный URL
            'name' => $name,
            'translations' => $translations,
        ];

        // Добавляем подкатегории, если они загружены
        if ($this->relationLoaded('children') && $this->children->isNotEmpty()) {
            $result['subcategories'] = $this->children->map(function ($child) use ($locale) {
                $childTranslations = $child->translations
                    ->groupBy('locale')
                    ->map(function ($trans) {
                        return $trans->pluck('value', 'code');
                    });
                $childName = $childTranslations[$locale]['name'] ?? null;
                if (!$childName && $childTranslations->count() > 0) {
                    $childName = $childTranslations[array_key_first($childTranslations->toArray())]['name'] ?? null;
                }
                return [
                    'id' => $child->id,
                    'name' => $childName,
                    'translations' => $childTranslations,
                ];
            })->values();
        } else {
            $result['subcategories'] = [];
        }

        return $result;
    }
}
