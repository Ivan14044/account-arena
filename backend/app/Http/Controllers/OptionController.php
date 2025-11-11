<?php

namespace App\Http\Controllers;

use App\Models\Option;
use Illuminate\Support\Facades\Cache;

class OptionController extends Controller
{
    /**
     * Получить все опции с кешированием (кеш на 1 час)
     */
    public function index()
    {
        $options = Cache::remember('site_options', 3600, function () {
            return Option::pluck('value', 'name')->toArray();
        });
        
        return response()->json($options);
    }

    /**
     * Получить правила покупки с кешированием (кеш на 1 час)
     */
    public function getPurchaseRules()
    {
        $rules = Cache::remember('purchase_rules', 3600, function () {
            $enabled = Option::get('purchase_rules_enabled', false);
            
            // Приводим к boolean (на случай если в БД хранится как строка)
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
            
            // Если правила отключены, возвращаем пустой ответ
            if (!$enabled) {
                return [
                    'enabled' => false,
                    'rules' => [
                        'ru' => '',
                        'en' => '',
                        'uk' => '',
                    ],
                ];
            }

            // Получаем правила для всех языков
            return [
                'enabled' => true,
                'rules' => [
                    'ru' => Option::get('purchase_rules_ru', ''),
                    'en' => Option::get('purchase_rules_en', ''),
                    'uk' => Option::get('purchase_rules_uk', ''),
                ],
            ];
        });
        
        return response()->json($rules);
    }
}
