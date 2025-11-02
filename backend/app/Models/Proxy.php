<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    protected $fillable = [
        'type',
        'address',
        'credentials',
        'country',
        'is_active',
        'expiring_at',
    ];

    protected $casts = [
        'expiring_at' => 'datetime',
    ];

    public function getFullProxy(): string
    {
        $proxy = strtolower($this->type) . '://';
        if (!empty($this->credentials)) {
            $proxy .= $this->credentials . '@';
        }

        return $proxy . $this->address;
    }
}
