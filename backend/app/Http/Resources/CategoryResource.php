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

        // Handle absolute image URL
        $imageUrl = $this->image_url;
        if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
            $imageUrl = url($imageUrl);
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'image_url' => $imageUrl,
            'name' => $name,
            'translations' => $translations,
            'subcategories' => self::collection($this->whenLoaded('children')),
        ];
    }
}
