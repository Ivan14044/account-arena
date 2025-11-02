<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'locale',
        'code',
        'value',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}


