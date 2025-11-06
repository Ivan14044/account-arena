<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ProductDispute;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    /**
     * Отображение списка претензий на товары поставщика
     */
    public function index(Request $request)
    {
        $supplier = auth()->user();

        $query = ProductDispute::with(['user', 'serviceAccount', 'transaction', 'resolver'])
            ->forSupplier($supplier->id);

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по дате
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $disputes = $query->latest()->paginate(20)->withQueryString();

        // Статистика
        $stats = [
            'total' => ProductDispute::forSupplier($supplier->id)->count(),
            'new' => ProductDispute::forSupplier($supplier->id)->new()->count(),
            'in_review' => ProductDispute::forSupplier($supplier->id)->inReview()->count(),
            'resolved' => ProductDispute::forSupplier($supplier->id)->resolved()->count(),
            'rejected' => ProductDispute::forSupplier($supplier->id)->rejected()->count(),
            'total_refunded' => ProductDispute::forSupplier($supplier->id)
                ->where('admin_decision', 'refund')
                ->sum('refund_amount'),
        ];

        return view('supplier.disputes.index', compact('disputes', 'stats'));
    }

    /**
     * Отображение детальной информации о претензии
     */
    public function show(ProductDispute $dispute)
    {
        $supplier = auth()->user();

        // Проверяем, что претензия принадлежит этому поставщику
        if ($dispute->supplier_id !== $supplier->id) {
            abort(403, 'У вас нет доступа к этой претензии');
        }

        $dispute->load(['user', 'serviceAccount', 'transaction', 'resolver']);

        return view('supplier.disputes.show', compact('dispute'));
    }
}
