<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'is_system',
    ];

    const TRANSLATION_FIELDS = [
        'meta_title',
        'meta_description',
        'title',
        'content',
    ];

    public function translations()
    {
        return $this->hasMany(PageTranslation::class);
    }
    
    /**
     * Получить перевод для указанного поля и локали
     */
    public function translate(string $code, string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Загружаем переводы, если они еще не загружены
        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }
        
        $translation = $this->translations
            ->where('locale', $locale)
            ->where('code', $code)
            ->first();

        return $translation ? $translation->value : null;
    }
}
