<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = [
        'name',
        'value',
    ];

    public static function set(string $name, $value): self
    {
        $value = is_array($value) ? json_encode($value) : $value;

        return self::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );
    }

    public static function get(string $name, $defaultValue = null)
    {
        return self::where('name', $name)->first()->value ?? $defaultValue;
    }
}
