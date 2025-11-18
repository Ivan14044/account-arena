<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDispute;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;

class ProductDisputeController extends Controller
{
    /**
     * Отображение списка претензий
     */
    public function index(Request $request)
    {
        $query = ProductDispute::with(['user', 'supplier', 'serviceAccount', 'transaction']);

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по владельцу товара (администратор или поставщики)
        if ($request->filled('owner')) {
            if ($request->owner === 'admin') {
                // Только товары администратора (supplier_id = NULL)
                $query->whereNull('supplier_id');
            } elseif ($request->owner === 'suppliers') {
                // Только товары поставщиков (supplier_id заполнен)
                $query->whereNotNull('supplier_id');
            }
        }

        // Фильтр по поставщику
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Фильтр по дате создания
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Поиск по ID претензии или email пользователя
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $disputes = $query->latest()->paginate(20)->withQueryString();

        // Статистика
        $stats = [
            'new' => ProductDispute::new()->count(),
            'in_review' => ProductDispute::inReview()->count(),
            'resolved' => ProductDispute::resolved()->count(),
            'rejected' => ProductDispute::rejected()->count(),
            'admin_products' => ProductDispute::whereNull('supplier_id')->count(),
            'supplier_products' => ProductDispute::whereNotNull('supplier_id')->count(),
        ];

        return view('admin.disputes.index', compact('disputes', 'stats'));
    }

    /**
     * Отображение детальной информации о претензии
     */
    public function show(ProductDispute $dispute)
    {
        $dispute->load(['user', 'supplier', 'serviceAccount', 'transaction', 'resolver']);

        return view('admin.disputes.show', compact('dispute'));
    }

    /**
     * Обработка претензии (возврат средств)
     */
    public function resolveRefund(Request $request, ProductDispute $dispute)
    {
        $request->validate([
            'admin_comment' => 'required|string|max:1000',
            'deduct_from_supplier' => 'boolean',
        ]);

        // Проверяем, что претензия еще не обработана
        if ($dispute->status === ProductDispute::STATUS_RESOLVED || 
            $dispute->status === ProductDispute::STATUS_REJECTED) {
            return back()->with('error', 'Претензия уже обработана!');
        }

        // Устанавливаем сумму возврата
        $dispute->refund_amount = $dispute->transaction->amount;

        // Обрабатываем возврат
        try {
            $dispute->resolveWithRefund(auth()->id(), $request->admin_comment);
            
            return redirect()->route('admin.disputes.index')
                ->with('success', 'Претензия обработана. Средства возвращены покупателю.');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при обработке претензии: ' . $e->getMessage());
        }
    }

    /**
     * Обработка претензии (замена товара)
     */
    public function resolveReplacement(Request $request, ProductDispute $dispute)
    {
        $request->validate([
            'admin_comment' => 'required|string|max:1000',
            'replacement_account_id' => 'required|exists:service_accounts,id',
        ]);

        // Проверяем, что претензия еще не обработана
        if ($dispute->status === ProductDispute::STATUS_RESOLVED || 
            $dispute->status === ProductDispute::STATUS_REJECTED) {
            return back()->with('error', 'Претензия уже обработана!');
        }

        try {
            $dispute->resolveWithReplacement(auth()->id(), $request->admin_comment);

            // Выдаем новый товар пользователю
            $replacementAccount = ServiceAccount::findOrFail($request->replacement_account_id);
            $replacementAccount->used = 1;
            $replacementAccount->used_by = $dispute->user_id;
            $replacementAccount->used_at = now();
            $replacementAccount->save();

            return redirect()->route('admin.disputes.index')
                ->with('success', 'Претензия обработана. Товар заменен.');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при обработке претензии: ' . $e->getMessage());
        }
    }

    /**
     * Отклонение претензии
     */
    public function reject(Request $request, ProductDispute $dispute)
    {
        $request->validate([
            'admin_comment' => 'required|string|max:1000',
        ]);

        // Проверяем, что претензия еще не обработана
        if ($dispute->status === ProductDispute::STATUS_RESOLVED || 
            $dispute->status === ProductDispute::STATUS_REJECTED) {
            return back()->with('error', 'Претензия уже обработана!');
        }

        try {
            $dispute->reject(auth()->id(), $request->admin_comment);

            return redirect()->route('admin.disputes.index')
                ->with('success', 'Претензия отклонена.');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при обработке претензии: ' . $e->getMessage());
        }
    }

    /**
     * Изменить статус на "На рассмотрении"
     */
    public function markInReview(ProductDispute $dispute)
    {
        if ($dispute->status === ProductDispute::STATUS_NEW) {
            $dispute->update(['status' => ProductDispute::STATUS_IN_REVIEW]);
            return back()->with('success', 'Статус изменен на "На рассмотрении"');
        }

        return back()->with('error', 'Невозможно изменить статус');
    }

    /**
     * Получить доступные товары для замены
     */
    public function getReplacementProducts(Request $request, ProductDispute $dispute)
    {
        $serviceId = $dispute->serviceAccount->service_id ?? null;

        if (!$serviceId) {
            return response()->json(['products' => []]);
        }

        $products = ServiceAccount::where('service_id', $serviceId)
            ->where('used', 0)
            ->where('supplier_id', $dispute->supplier_id)
            ->select('id', 'title', 'login', 'service_id')
            ->limit(50)
            ->get();

        return response()->json(['products' => $products]);
    }

    /**
     * Получить количество новых претензий для админа
     */
    public function getNewCount()
    {
        $newCount = ProductDispute::where('status', ProductDispute::STATUS_NEW)->count();
        
        return response()->json(['count' => $newCount]);
    }
}
