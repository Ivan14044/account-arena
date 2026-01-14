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
        // Используем кеширование всех опций для минимизации запросов к БД
        $options = \Illuminate\Support\Facades\Cache::remember('site_options_all', 3600, function() {
            return self::pluck('value', 'name')->toArray();
        });

        return $options[$name] ?? $defaultValue;
    }
}
