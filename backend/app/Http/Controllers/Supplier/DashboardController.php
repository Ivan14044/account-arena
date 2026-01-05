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
        
        // ВАЖНО: Синхронизируем баланс поставщика из SupplierEarning
        // Это гарантирует, что supplier_balance всегда актуален
        $this->syncSupplierBalance($supplier);
        
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

    /**
     * Синхронизирует supplier_balance из SupplierEarning
     * Переводит средства из held в available и обновляет баланс
     */
    private function syncSupplierBalance($supplier)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($supplier) {
                // Находим earnings, готовые к переводу
                $readyToRelease = \App\Models\SupplierEarning::where('supplier_id', $supplier->id)
                    ->where('status', 'held')
                    ->whereNotNull('available_at')
                    ->where('available_at', '<=', now())
                    ->lockForUpdate()
                    ->get();

                if ($readyToRelease->isEmpty()) {
                    return; // Нет средств для перевода
                }

                $totalAmount = $readyToRelease->sum('amount');

                // Обновляем статус на 'available'
                $readyToRelease->each(function ($earning) {
                    $earning->update([
                        'status' => 'available',
                        'processed_at' => now(),
                    ]);
                });

                // Увеличиваем баланс поставщика
                $supplier->increment('supplier_balance', $totalAmount);

                \Illuminate\Support\Facades\Log::info('Supplier balance synced on dashboard load', [
                    'supplier_id' => $supplier->id,
                    'earnings_count' => $readyToRelease->count(),
                    'amount_added' => $totalAmount,
                    'new_balance' => $supplier->fresh()->supplier_balance,
                ]);
            });
        } catch (\Throwable $e) {
            // Не ломаем загрузку dashboard, но логируем ошибку
            \Illuminate\Support\Facades\Log::error('Failed to sync supplier balance on dashboard', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
