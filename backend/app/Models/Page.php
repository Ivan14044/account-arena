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
}
