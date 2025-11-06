<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class PurchaseRulesController extends Controller
{
    /**
     * Display the purchase rules management page
     */
    public function index()
    {
        $rules_ru = Option::get('purchase_rules_ru', '');
        $rules_en = Option::get('purchase_rules_en', '');
        $rules_uk = Option::get('purchase_rules_uk', '');
        $rules_enabled = Option::get('purchase_rules_enabled', false);

        return view('admin.purchase-rules.index', compact('rules_ru', 'rules_en', 'rules_uk', 'rules_enabled'));
    }

    /**
     * Store/Update purchase rules
     */
    public function store(Request $request)
    {
        $request->validate([
            'purchase_rules_ru' => 'nullable|string|max:10000',
            'purchase_rules_en' => 'nullable|string|max:10000',
            'purchase_rules_uk' => 'nullable|string|max:10000',
            'purchase_rules_enabled' => 'nullable|boolean',
        ]);

        // Сохраняем правила для каждого языка
        Option::set('purchase_rules_ru', $request->input('purchase_rules_ru', ''));
        Option::set('purchase_rules_en', $request->input('purchase_rules_en', ''));
        Option::set('purchase_rules_uk', $request->input('purchase_rules_uk', ''));
        // Сохраняем как 1 или 0 для корректной работы с boolean
        Option::set('purchase_rules_enabled', $request->has('purchase_rules_enabled') ? 1 : 0);

        return redirect()
            ->route('admin.purchase-rules.index')
            ->with('success', 'Правила покупки успешно обновлены');
    }
}

