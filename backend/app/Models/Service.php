<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'code',
        'logo',
        'is_active',
        'amount',
        'trial_amount',
        'position',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    const TRANSLATION_FIELDS = [
        'name',
        'subtitle',
        'short_description_card',
        'short_description_checkout',
        'full_description',
    ];

    const DEFAULT_LOGO = '/img/no-logo.png';

    public function translations()
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    public function accounts()
    {
        return $this->hasMany(ServiceAccount::class);
    }

    public function scopeWithRussianName($query)
    {
        return $query->with(['translations' => function ($q) {
            $q->where('locale', 'ru')->where('code', 'name');
        }]);
    }

    public function getAdminNameAttribute()
    {
        return optional($this->translations->first())->value;
    }

    public static function nextPosition(): int
    {
        return static::max('position') + 1;
    }

    public function getTranslation(string $code, string $locale = 'en'): ?string
    {
        $translations = $this->translations;

        $translation = $translations
            ->where('code', $code)
            ->where('locale', $locale)
            ->first();

        if (!$translation) {
            $translation = $translations
                ->where('code', $code)
                ->where('locale', 'en')
                ->first();
        }

        return $translation?->value;
    }
}
