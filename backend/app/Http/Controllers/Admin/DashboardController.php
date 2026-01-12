<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'today');
        $startDate = null;
        $endDate = Carbon::now()->endOfDay(); // по умолчанию до конца сегодняшнего дня

        switch ($period) {
            case 'today':
                $startDate = Carbon::today()->startOfDay();
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday()->startOfDay();
                $endDate = Carbon::yesterday()->endOfDay();
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek()->startOfDay();
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear()->startOfDay();
                break;
            case 'all':
                // За весь период - не устанавливаем даты
                $startDate = null;
                $endDate = null;
                break;
            case 'custom':
                if ($request->filled('start_date')) {
                    $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                }
                if ($request->filled('end_date')) {
                    $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                }
                break;
        }

        $dateFilter = function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        };

        // Всего товаров в магазине (количество позиций)
        $totalProducts = ServiceAccount::where('is_active', true)
            ->whereNotNull('title')
            ->whereNotNull('price')
            ->count();

        // Всего товара на сумму
        $allProducts = ServiceAccount::where('is_active', true)
            ->whereNotNull('price')
            ->get();

        $totalProductsValue = $allProducts->sum(function ($product) {
            $quantity = is_array($product->accounts_data) ? count($product->accounts_data) : 0;
            return $quantity * (float)$product->price;
        });

        // Доступно для продажи
        $availableProducts = $allProducts->sum(function ($product) {
            $totalQuantity = is_array($product->accounts_data) ? count($product->accounts_data) : 0;
            $soldCount = $product->used ?? 0;
            return max(0, $totalQuantity - $soldCount);
        });

        // Покупки товаров за период
        $purchasesQuery = Purchase::query();
        if ($startDate && $endDate) {
            $purchasesQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $purchasesInPeriod = $purchasesQuery->count();

        // Продано за период (количество завершенных покупок)
        $soldInPeriodQuery = Purchase::where('status', 'completed');
        if ($startDate && $endDate) {
            $soldInPeriodQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $soldInPeriod = $soldInPeriodQuery->count();

        // Доход за период (сумма продаж товаров за период)
        $revenueInPeriodQuery = Purchase::where('status', 'completed');
        if ($startDate && $endDate) {
            $revenueInPeriodQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $revenueInPeriod = $revenueInPeriodQuery->sum('total_amount');

        // Средний чек за период (только по завершенным покупкам)
        $averageOrderValue = $soldInPeriod > 0 ? ($revenueInPeriod / $soldInPeriod) : 0;

        // Всего пользователей
        $totalUsers = User::where('is_admin', false)->where('is_main_admin', false)->count();

        // Данные для графика продаж (последние 30 дней)
        $salesChartData = $this->getSalesChartData(30);
        
        // Данные для графика по категориям
        $categoryChartData = $this->getCategoryChartData();
        
        // Топ-5 продаваемых товаров
        $topProducts = $this->getTopProducts(5);

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalProductsValue',
            'availableProducts',
            'purchasesInPeriod',
            'soldInPeriod',
            'revenueInPeriod',
            'averageOrderValue',
            'totalUsers',
            'period',
            'startDate',
            'endDate',
            'salesChartData',
            'categoryChartData',
            'topProducts'
        ));
    }

    /**
     * Получить данные для графика продаж по дням
     * ИСПРАВЛЕНО: считаем из Purchase, а не Transaction
     */
    private function getSalesChartData($days = 30)
    {
        $data = [];
        $labels = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            
            // ИСПРАВЛЕНО: считаем продажи товаров из Purchase
            $sales = Purchase::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'completed')
                ->sum('total_amount');
            
            $labels[] = $date->format('d.m');
            $data[] = round($sales, 2);
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Получить данные для графика по категориям
     */
    private function getCategoryChartData()
    {
        $products = ServiceAccount::with('category')
            ->whereHas('category')
            ->get();
        
        $categoryData = [];
        
        foreach ($products as $product) {
            $categoryName = $product->category?->admin_name ?? 'Без категории';
            if (!isset($categoryData[$categoryName])) {
                $categoryData[$categoryName] = 0;
            }
            $categoryData[$categoryName] += $product->used ?? 0;
        }
        
        $labels = array_keys($categoryData);
        $data = array_values($categoryData);
        
        // Генерируем цвета для графиков
        $colors = [];
        for ($i = 0; $i < count($labels); $i++) {
            $hue = (360 / count($labels)) * $i;
            $colors[] = "hsl({$hue}, 70%, 60%)";
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    /**
     * Получить топ продаваемых товаров
     */
    private function getTopProducts($limit = 5)
    {
        return ServiceAccount::where('is_active', true)
            ->whereNotNull('title')
            ->orderBy('used', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sold' => $product->used ?? 0,
                    'revenue' => round(($product->used ?? 0) * (float)$product->price, 2),
                ];
            });
    }

}
