@extends('adminlte::page')

@section('title', 'Панель управления')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">Панель управления</h1>
            </div>
            <div>
                <form method="GET" class="mb-0" style="max-width: 300px;">
                    <div class="input-group">
                        <select name="period" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Сегодня</option>
                            <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Вчера</option>
                            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>На этой неделе</option>
                            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>В этом месяце</option>
                            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>В этом году</option>
                            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>За весь период</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Произвольный период</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if($period === 'custom')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="mb-0">
                            <div class="form-row align-items-end">
                                <div class="col-md-3">
                                    <label class="mb-1"><small>Дата начала</small></label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="mb-1"><small>Дата окончания</small></label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="hidden" name="period" value="custom">
                                    <button type="submit" class="btn btn-primary">Применить</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Основные метрики -->
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего товаров</div>
                        <div class="stat-value">{{ $totalProducts }}</div>
                        <a href="{{ route('admin.service-accounts.index') }}" class="text-primary">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Доступно для продажи</div>
                        <div class="stat-value">{{ number_format($availableProducts, 0) }}</div>
                        <a href="{{ route('admin.service-accounts.index') }}" class="text-success">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-danger">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Продано всего</div>
                        <div class="stat-value">{{ number_format($totalSold, 0) }}</div>
                        <a href="{{ route('admin.purchases.index') }}" class="text-danger">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Общий доход</div>
                        <div class="stat-value">{{ number_format($totalProfit, 2) }}</div>
                        <div class="text-muted small mb-2">{{ \App\Models\Option::get('currency') }}</div>
                        <a href="{{ route('admin.purchases.index') }}" class="text-info">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика за период -->
    <div class="row mt-4">
        <div class="col-12 mb-3">
            <h5 class="text-muted mb-0"><i class="fas fa-calendar-day mr-2"></i>Статистика продаж</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-cart-plus"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Покупки товаров</div>
                        <div class="stat-value">{{ $purchasesToday }}</div>
                        <a href="{{ route('admin.purchases.index') }}" class="text-success">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Сумма продаж</div>
                        <div class="stat-value">{{ number_format($salesToday, 2) }}</div>
                        <div class="text-muted small mb-2">{{ \App\Models\Option::get('currency') }}</div>
                        <a href="{{ route('admin.purchases.index') }}" class="text-warning">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Товаров на сумму</div>
                        <div class="stat-value">{{ number_format($totalProductsValue, 2) }}</div>
                        <div class="text-muted small mb-2">{{ \App\Models\Option::get('currency') }}</div>
                        <a href="{{ route('admin.service-accounts.index') }}" class="text-info">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего пользователей</div>
                        <div class="stat-value">{{ $totalUsers }}</div>
                        <a href="{{ route('admin.users.index') }}" class="text-primary">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Графики и аналитика -->
    <div class="row mt-4">
        <div class="col-12 mb-3">
            <h5 class="text-muted mb-0"><i class="fas fa-chart-bar mr-2"></i>Аналитика продаж</h5>
        </div>
    </div>

    <div class="row">
        <!-- График продаж за 30 дней -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Продажи за последние 30 дней</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Круговой график по категориям -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Продажи по категориям</h6>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Топ товаров -->
    @if(count($topProducts) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-fire mr-2"></i>Топ продаваемых товаров</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th class="text-center">Продано</th>
                                        <th class="text-right">Выручка</th>
                                        <th class="text-center">Действие</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProducts as $product)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.service-accounts.edit', $product['id']) }}" class="text-dark">
                                                    {{ $product['title'] }}
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-success">{{ $product['sold'] }} шт.</span>
                                            </td>
                                            <td class="text-right font-weight-bold text-success">
                                                ${{ number_format($product['revenue'], 2) }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.service-accounts.edit', $product['id']) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('css')
    @include('admin.layouts.modern-styles')
<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endsection

@section('js')
<script>
    // Wait for Chart.js to load and DOM to be ready
    function initCharts() {
        // Check if Chart is available
        if (typeof Chart === 'undefined') {
            // Retry after a short delay if Chart.js is still loading
            setTimeout(initCharts, 100);
            return;
        }

        // Check if chart elements exist
        const salesChartElement = document.getElementById('salesChart');
        const categoryChartElement = document.getElementById('categoryChart');
        
        if (!salesChartElement && !categoryChartElement) {
            return; // No charts to initialize
        }

        // График продаж
        if (salesChartElement) {
            const salesCtx = salesChartElement.getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($salesChartData['labels']) !!},
                    datasets: [{
                        label: 'Продажи',
                        data: {!! json_encode($salesChartData['data']) !!},
                        borderColor: 'rgb(0, 123, 255)',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }

        // График по категориям
        if (categoryChartElement) {
            const categoryCtx = categoryChartElement.getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($categoryChartData['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($categoryChartData['data']) !!},
                        backgroundColor: {!! json_encode($categoryChartData['colors']) !!}
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' шт.';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize charts when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        // DOM is already ready
        initCharts();
    }
</script>
@stop
