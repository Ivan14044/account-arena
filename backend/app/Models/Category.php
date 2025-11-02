<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public $fillable = ['type', 'image_url'];

    public $hidden = ['updated_at', 'pivot'];

    protected $casts = [
        'type' => 'string',
    ];

    // Types
    const TYPE_PRODUCT = 'product';
    const TYPE_ARTICLE = 'article';

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_category');
    }

    public function products()
    {
        return $this->hasMany(ServiceAccount::class);
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($category) {
            $category->articles()->detach();
        });
    }

    // Scopes
    public function scopeProductCategories($query)
    {
        return $query->where('type', self::TYPE_PRODUCT);
    }

    public function scopeArticleCategories($query)
    {
        return $query->where('type', self::TYPE_ARTICLE);
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translate(string $code, string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->translations
            ->where('locale', $locale)
            ->where('code', $code)
            ->first();

        return $translation ? $translation->value : null;
    }

    public function saveTranslation(array $validated): void
    {
        $this->translations()->whereIn('code', ['name', 'meta_title', 'meta_description', 'text'])->delete();

        foreach (config('langs') as $locale => $langValue) {
            $name = $validated['name'][$locale] ?? null;
            if ($name !== null && $name !== '') {
                $this->translations()->updateOrCreate(
                    ['locale' => $locale, 'code' => 'name'],
                    ['value' => $name]
                );
            }

            $metaTitle = $validated['meta_title'][$locale] ?? null;
            if ($metaTitle !== null && $metaTitle !== '') {
                $this->translations()->updateOrCreate(
                    ['locale' => $locale, 'code' => 'meta_title'],
                    ['value' => $metaTitle]
                );
            }

            $metaDescription = $validated['meta_description'][$locale] ?? null;
            if ($metaDescription !== null && $metaDescription !== '') {
                $this->translations()->updateOrCreate(
                    ['locale' => $locale, 'code' => 'meta_description'],
                    ['value' => $metaDescription]
                );
            }

            $text = $validated['text'][$locale] ?? null;
            if ($text !== null && $text !== '') {
                $this->translations()->updateOrCreate(
                    ['locale' => $locale, 'code' => 'text'],
                    ['value' => $text]
                );
            }
        }
    }

    public function getAdminNameAttribute()
    {
        if ($this->relationLoaded('translations')) {
            $ruName = $this->translations
                ->where('code', 'name')
                ->where('locale', 'ru')
                ->first();
            if ($ruName) {
                return $ruName->value;
            }
            $anyName = $this->translations
                ->where('code', 'name')
                ->first();
            return $anyName?->value;
        }
        return null;
    }
}
