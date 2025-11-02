<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateTranslation extends Model
{
    protected $fillable = [
        'email_template_id',
        'locale',
        'code',
        'value',
    ];
}
