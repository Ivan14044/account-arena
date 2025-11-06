<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $supplier = auth()->user();
        $supplierId = $supplier->id;
        
        // Статистика по товарам поставщика
        $products = ServiceAccount::where('supplier_id', $supplierId)->get();
        $totalProducts = $products->count();
        $activeProducts = $products->where('is_active', true)->count();
        
        // Подсчет общего количества товаров в наличии
        $totalStock = 0;
        $soldCount = 0;
        $lowStockProducts = [];
        
        foreach ($products as $product) {
            $available = $product->getAvailableStock();
            $totalStock += $available;
            $soldCount += $product->used ?? 0;
            
            if ($product->is_active && $product->isLowStock() && $available > 0) {
                $lowStockProducts[] = $product;
            }
        }
        
        // Транзакции за последние 30 дней (если есть модель Transaction)
        $thirtyDaysAgo = now()->subDays(30);
        $transactions = Transaction::whereHas('serviceAccount', function($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'completed')
            ->get() ?? collect();
        
        // Расчет статистики
        $totalRevenue = $transactions->sum('amount');
        $totalOrders = $transactions->count();
        $averageCheck = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // График продаж за последние 7 дней
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayRevenue = $transactions->filter(function($t) use ($date) {
                return $t->created_at->format('Y-m-d') === $date;
            })->sum('amount');
            
            $last7Days[] = [
                'date' => now()->subDays($i)->format('d.m'),
                'revenue' => (float) $dayRevenue
            ];
        }
        
        // Топ-5 самых продаваемых товаров
        $topProducts = ServiceAccount::where('supplier_id', $supplierId)
            ->where('used', '>', 0)
            ->orderByDesc('used')
            ->take(5)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sold' => $product->used ?? 0,
                    'revenue' => ($product->used ?? 0) * $product->price,
                    'stock' => $product->getAvailableStock()
                ];
            });
        
        // Непрочитанные уведомления
        $unreadNotifications = $supplier->supplierNotifications()
            ->unread()
            ->latest()
            ->take(5)
            ->get();
        
        $unreadCount = $supplier->supplierNotifications()->unread()->count();
        
        // Рейтинг поставщика
        $rating = $supplier->supplier_rating ?? 100.00;
        $ratingLevel = $supplier->getRatingLevel();
        $ratingDetails = $supplier->getRatingDetails();
        
        return view('supplier.dashboard', compact(
            'supplier',
            'totalProducts',
            'activeProducts',
            'totalStock',
            'soldCount',
            'totalRevenue',
            'totalOrders',
            'averageCheck',
            'last7Days',
            'topProducts',
            'lowStockProducts',
            'unreadNotifications',
            'unreadCount',
            'rating',
            'ratingLevel',
            'ratingDetails'
        ));
    }
}
