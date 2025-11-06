<?php

namespace App\Http\Controllers;

use App\Models\Option;

class OptionController extends Controller
{
    public function index()
    {
        return response()->json(Option::pluck('value', 'name')->toArray());
    }

    /**
     * Get purchase rules based on current language
     */
    public function getPurchaseRules()
    {
        $enabled = Option::get('purchase_rules_enabled', false);
        
        // Приводим к boolean (на случай если в БД хранится как строка)
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        
        // Если правила отключены, возвращаем пустой ответ
        if (!$enabled) {
            return response()->json([
                'enabled' => false,
                'rules' => [
                    'ru' => '',
                    'en' => '',
                    'uk' => '',
                ],
            ]);
        }

        // Получаем правила для всех языков
        return response()->json([
            'enabled' => true,
            'rules' => [
                'ru' => Option::get('purchase_rules_ru', ''),
                'en' => Option::get('purchase_rules_en', ''),
                'uk' => Option::get('purchase_rules_uk', ''),
            ],
        ]);
    }
}
