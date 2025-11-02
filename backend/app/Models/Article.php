<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasFactory, HasTranslations;

    const TRANSLATION_FIELDS = [
        'title',
        'content',
        'meta_title',
        'meta_description',
        'short',
    ];

    public $fillable = [
        "description",
        "img",
        "status",
        "created_at"
    ];

    public $hidden = ['updated_at'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category');
    }

    public function translations()
    {
        return $this->hasMany(ArticleTranslation::class);
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

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($article) {
            $article->categories()->detach();
        });
    }

    public function scopeWithRussianTitle($query)
    {
        return $query->with(['translations' => function ($q) {
            $q->where('locale', 'ru')->where('code', 'title');
        }]);
    }

    public function getAdminNameAttribute()
    {
        if ($this->relationLoaded('translations')) {
            $ruTitle = $this->translations
                ->where('code', 'title')
                ->where('locale', 'ru')
                ->first();
            if ($ruTitle) {
                return $ruTitle->value;
            }
            $anyTitle = $this->translations
                ->where('code', 'title')
                ->first();
            return $anyTitle?->value;
        }
        return null;
    }
}
