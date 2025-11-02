<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplateTranslation extends Model
{
    protected $fillable = [
        'notification_template_id',
        'locale',
        'code',
        'value',
    ];
}
