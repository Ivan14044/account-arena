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
        $endDate = Carbon::now()->endOfDay();

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
            case 'custom':
                if ($request->filled('start_date')) {
                    $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                }
                if ($request->filled('end_date')) {
                    $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                }
                break;
        }

        // КЕШИРОВАНИЕ: Сохраняем статистику на 10 минут (кроме кастомных дат)
        $cacheKey = 'admin_dashboard_' . $period . ($period === 'custom' ? '_' . md5($startDate . $endDate) : '');
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function() use ($startDate, $endDate, $period) {
            // ОПТИМИЗИРОВАНО: Значения "всего" через SQL агрегацию с учетом типов доставки
            $stats = ServiceAccount::where('is_active', true)
                ->where(function($query) {
                    $query->where('moderation_status', 'approved')
                          ->orWhereNull('supplier_id');
                })
                ->selectRaw("
                    COUNT(*) as total_products,
                    SUM(CASE 
                        WHEN delivery_type = 'manual' THEN 999 
                        ELSE GREATEST(0, JSON_LENGTH(accounts_data) - COALESCE(used, 0)) 
                    END) as available_products,
                    SUM(CASE 
                        WHEN delivery_type = 'manual' THEN 999 * price 
                        ELSE GREATEST(0, JSON_LENGTH(accounts_data) - COALESCE(used, 0)) * price 
                    END) as total_value
                ")
                ->first();

            $totalProducts = (int)($stats->total_products ?? 0);
            $totalProductsValue = (float)($stats->total_value ?? 0);
            $availableProducts = (int)($stats->available_products ?? 0);

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

            // Средний чек за период
            $averageOrderValue = $soldInPeriod > 0 ? ($revenueInPeriod / $soldInPeriod) : 0;

            // Всего пользователей
            $totalUsers = User::where('is_admin', false)->where('is_main_admin', false)->count();

            return [
                'totalProducts' => $totalProducts,
                'totalProductsValue' => $totalProductsValue,
                'availableProducts' => $availableProducts,
                'purchasesInPeriod' => $purchasesInPeriod,
                'soldInPeriod' => $soldInPeriod,
                'revenueInPeriod' => $revenueInPeriod,
                'averageOrderValue' => $averageOrderValue,
                'totalUsers' => $totalUsers,
                'salesChartData' => $this->getSalesChartData(30),
                'categoryChartData' => $this->getCategoryChartData(),
                'topProducts' => $this->getTopProducts(5),
            ];
        });

        return view('admin.dashboard', array_merge($data, [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]));
    }

    /**
     * Получить данные для графика продаж по дням
     * ИСПРАВЛЕНО: считаем из Purchase, а не Transaction
     */
    private function getSalesChartData($days = 30)
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        
        // ОПТИМИЗИРОВАНО: Используем прямое сравнение дат. 
        // Для MySQL индекс на created_at будет работать эффективнее, если не оборачивать его в DATE() в WHERE.
        // Группировка по DATE(created_at) всё равно нужна для агрегации.
        $results = Purchase::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'asc') // Добавлена сортировка для надежности
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $data = [];
        $labels = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            
            $labels[] = $date->format('d.m');
            $data[] = round($results[$dateKey] ?? 0, 2);
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Получить данные для графика по категориям
     * ИСПРАВЛЕНО: Считаем продажи по реальным покупкам в категориях
     */
    private function getCategoryChartData()
    {
        // Используем оптимизированный SQL-запрос для получения статистики по категориям
        $results = \Illuminate\Support\Facades\DB::table('purchases')
            ->join('service_accounts', 'purchases.service_account_id', '=', 'service_accounts.id')
            ->join('categories', 'service_accounts.category_id', '=', 'categories.id')
            ->join('category_translations', function($join) {
                $join->on('categories.id', '=', 'category_translations.category_id')
                    ->where('category_translations.code', '=', 'name')
                    ->where('category_translations.locale', '=', 'ru');
            })
            ->where('purchases.status', '=', 'completed')
            ->select('category_translations.value as category_name', \Illuminate\Support\Facades\DB::raw('count(purchases.id) as sales_count'))
            ->groupBy('category_translations.value')
            ->get();
        
        $labels = $results->pluck('category_name')->toArray();
        $data = $results->pluck('sales_count')->toArray();
        
        // Генерируем цвета для графиков
        $colors = [];
        for ($i = 0; $i < count($labels); $i++) {
            $hue = (360 / max(1, count($labels))) * $i;
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
     * ИСПРАВЛЕНО: Считаем выручку по реальным покупкам, а не по текущей цене
     */
    private function getTopProducts($limit = 5)
    {
        return Purchase::where('status', 'completed')
            ->selectRaw('service_account_id, count(*) as sold, sum(total_amount) as revenue')
            ->groupBy('service_account_id')
            ->orderByDesc('sold')
            ->limit($limit)
            ->with('serviceAccount:id,title')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->service_account_id,
                    'title' => $purchase->serviceAccount->title ?? 'Удаленный товар',
                    'sold' => $purchase->sold,
                    'revenue' => round($purchase->revenue, 2),
                ];
            });
    }

}
