<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasTranslations;

    protected $fillable = [
        'code',
        'name',
    ];

    const TRANSLATION_FIELDS = [
        'title',
        'message',
    ];

    public function translations()
    {
        return $this->hasMany(EmailTemplateTranslation::class);
    }
}
