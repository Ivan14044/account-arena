<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromocodeUsage extends Model
{
    protected $fillable = [
        'promocode_id',
        'user_id',
        'order_id',
    ];

    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


