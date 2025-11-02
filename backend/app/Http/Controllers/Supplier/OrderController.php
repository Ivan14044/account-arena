<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $supplier = auth()->user();
        $supplierId = $supplier->id;
        
        // Base query
        $query = Transaction::with(['user', 'serviceAccount'])
            ->whereNotNull('service_account_id')
            ->whereHas('serviceAccount', function($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->where('status', 'completed');
        
        // Filters
        if ($request->filled('product_id')) {
            $query->where('service_account_id', $request->product_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('serviceAccount', function($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%");
                });
            });
        }
        
        // Pagination
        $orders = $query->latest()->paginate(20)->withQueryString();
        
        // Get supplier's products for filter
        $products = ServiceAccount::where('supplier_id', $supplierId)
            ->orderBy('title')
            ->get(['id', 'title']);
        
        // Statistics
        $totalOrders = $query->count();
        $totalRevenue = $query->sum('amount');
        
        return view('supplier.orders.index', compact(
            'orders',
            'products',
            'totalOrders',
            'totalRevenue'
        ));
    }
}

