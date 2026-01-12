<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\User;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Отображение списка всех покупок
     */
    public function index(Request $request)
    {
        $query = Purchase::with([
            'user' => function($q) {
                $q->select('id', 'name', 'email');
            },
            'serviceAccount' => function($q) {
                $q->select('id', 'title');
            },
            'transaction' => function($q) {
                $q->select('id', 'amount', 'currency', 'payment_method');
            }
        ]);

        // Фильтр по пользователю
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Фильтр по товару
        if ($request->filled('product_id')) {
            $query->where('service_account_id', $request->product_id);
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по дате создания
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Поиск по номеру заказа, email пользователя или гостевому email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%") // Поиск по гостевому email
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Фильтр по типу покупателя (зарегистрированный/гость)
        if ($request->filled('buyer_type')) {
            if ($request->buyer_type === 'registered') {
                $query->whereNotNull('user_id');
            } elseif ($request->buyer_type === 'guest') {
                $query->whereNull('user_id')->whereNotNull('guest_email');
            }
        }

        $purchases = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        // Получаем только необходимые данные для фильтров (ограничиваем выборку для производительности)
        // Если выбран конкретный пользователь/товар, загружаем его, иначе не грузим всё сразу
        $users = [];
        if ($request->filled('user_id')) {
            $users = User::where('id', $request->user_id)->get(['id', 'email']);
        }

        $products = [];
        if ($request->filled('product_id')) {
            $products = ServiceAccount::where('id', $request->product_id)->get(['id', 'title']);
        }

        // Статистика (кешируем на короткое время или оптимизируем запрос)
        $stats = [
            'total' => Purchase::count(),
            'today' => Purchase::whereDate('created_at', today())->count(),
            'this_month' => Purchase::whereMonth('created_at', now()->month)->count(),
            'total_revenue' => Purchase::where('status', 'completed')->sum('total_amount'),
        ];

        return view('admin.purchases.index', compact('purchases', 'users', 'products', 'stats'));
    }

    /**
     * Отображение конкретной покупки
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['user', 'serviceAccount', 'transaction']);
        
        return view('admin.purchases.show', compact('purchase'));
    }

    /**
     * Удаление покупки (только для отмененных)
     */
    public function destroy(Purchase $purchase)
    {
        if ($purchase->status === 'completed') {
            return back()->with('error', 'Нельзя удалить завершенную покупку. Сначала измените статус.');
        }

        $purchase->delete();

        return redirect()->route('admin.purchases.index')
            ->with('success', 'Покупка удалена');
    }
}
