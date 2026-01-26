@extends('adminlte::page')

@section('title', 'Панель управления')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div class="mb-2 mb-md-0">
                <h1 class="m-0 font-weight-bold text-dark">{{ __('Панель управления') }}</h1>
            </div>
            <div class="w-100 w-md-auto">
                <form method="GET" class="mb-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                        <select name="period" class="form-control border-left-0" onchange="this.form.submit()">
                            <option value="today" {{ $period === 'today' ? 'selected' : '' }}>{{ __('Сегодня') }}</option>
                            <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>{{ __('Вчера') }}</option>
                            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>{{ __('На этой неделе') }}</option>
                            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>{{ __('В этом месяце') }}</option>
                            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ __('В этом году') }}</option>
                            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('За весь период') }}</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>{{ __('Произвольный период') }}</option>
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
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="mb-0">
                            <div class="row">
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <label class="mb-1 text-muted small">{{ __('Дата начала') }}</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <label class="mb-1 text-muted small">{{ __('Дата окончания') }}</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <input type="hidden" name="period" value="custom">
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter mr-2"></i>{{ __('Применить') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Общая статистика -->
    <div class="row mb-3">
        <div class="col-12">
            <h5 class="text-secondary font-weight-bold">
                <i class="fas fa-database mr-2"></i>{{ __('Общая статистика') }}
            </h5>
        </div>
    </div>

    <div class="row dashboard-stats-row">
        <!-- Total Products -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-primary w-100">
                <div class="stat-card-body">
                    <div class="stat-main-info">
                        <div class="stat-label">{{ __('Всего товаров') }}</div>
                        <div class="stat-value">{{ number_format($totalProducts, 0) }}</div>
                    </div>
                    <a href="{{ route('admin.service-accounts.index') }}" class="stat-link text-primary">
                        {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon-bg">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>

        <!-- Available Products -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-info w-100">
                <div class="stat-card-body">
                    <div class="stat-main-info">
                        <div class="stat-label">{{ __('Доступно к продаже') }}</div>
                        <div class="stat-value">{{ number_format($availableProducts, 0) }}</div>
                    </div>
                    <a href="{{ route('admin.service-accounts.index') }}" class="stat-link text-info">
                        {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon-bg">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Total Value -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-warning w-100">
                <div class="stat-card-body">
                    <div class="stat-main-info">
                        <div class="stat-label">{{ __('Стоимость стока') }}</div>
                        <div class="stat-value">{{ number_format($totalProductsValue, 2) }}<span class="stat-unit">{{ \App\Models\Option::get('currency') }}</span></div>
                    </div>
                    <a href="{{ route('admin.service-accounts.index') }}" class="stat-link text-warning">
                        {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon-bg">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-secondary w-100">
                <div class="stat-card-body">
                    <div class="stat-main-info">
                        <div class="stat-label">{{ __('Пользователей') }}</div>
                        <div class="stat-value">{{ number_format($totalUsers, 0) }}</div>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="stat-link text-secondary">
                        {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon-bg">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика за период -->
    <div class="row mt-4 mb-3">
        <div class="col-12 border-bottom pb-2">
            <div class="d-flex align-items-center">
                <h5 class="text-dark font-weight-bold mb-0">
                    <i class="fas fa-chart-line mr-2 text-primary"></i>{{ __('Показатели за период') }}
                </h5>
                @php
                    $periodLabels = [
                        'today' => __('Сегодня'),
                        'yesterday' => __('Вчера'),
                        'week' => __('На этой неделе'),
                        'month' => __('В этом месяце'),
                        'year' => __('В этом году'),
                        'all' => __('За весь период'),
                        'custom' => __('Произвольный период')
                    ];
                    $periodLabel = $periodLabels[$period] ?? __('Выбранный период');
                @endphp
                <span class="badge badge-light border ml-3 px-3">{{ $periodLabel }}</span>
            </div>
        </div>
    </div>

    <div class="row dashboard-stats-row">
        <!-- Revenue (Most important = Success Color) -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-success w-100 shadow-sm border-0">
                <div class="stat-card-body p-4">
                    <div class="stat-main-info text-left">
                        <div class="stat-label text-uppercase small font-weight-bold opacity-70 mb-1">{{ __('Доход') }}</div>
                        <div class="stat-value h2 font-weight-bold mb-0">
                            {{ number_format($revenueInPeriod, 2) }}<span class="small font-weight-normal text-muted ml-1">{{ \App\Models\Option::get('currency') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-footer px-4 py-2 bg-light border-top rounded-bottom">
                    <a href="{{ route('admin.purchases.index') }}" class="stat-link text-success small font-weight-bold">
                        {{ __('Детали') }} <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="stat-icon-bg opacity-10">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>

        <!-- Purchases Count -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-primary w-100 shadow-sm border-0">
                <div class="stat-card-body p-4">
                    <div class="stat-main-info text-left">
                        <div class="stat-label text-uppercase small font-weight-bold opacity-70 mb-1">{{ __('Заказов') }}</div>
                        <div class="stat-value h2 font-weight-bold mb-0">{{ number_format($purchasesInPeriod, 0) }}</div>
                    </div>
                </div>
                <div class="stat-card-footer px-4 py-2 bg-light border-top rounded-bottom">
                    <a href="{{ route('admin.purchases.index') }}" class="stat-link text-primary small font-weight-bold">
                        {{ __('Детали') }} <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="stat-icon-bg opacity-10">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>

        <!-- Sold Items -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-info w-100 shadow-sm border-0">
                <div class="stat-card-body p-4">
                    <div class="stat-main-info text-left">
                        <div class="stat-label text-uppercase small font-weight-bold opacity-70 mb-1">{{ __('Товаров продано') }}</div>
                        <div class="stat-value h2 font-weight-bold mb-0">{{ number_format($soldInPeriod, 0) }}</div>
                    </div>
                </div>
                <div class="stat-card-footer px-4 py-2 bg-light border-top rounded-bottom">
                    <a href="{{ route('admin.purchases.index') }}" class="stat-link text-info small font-weight-bold">
                        {{ __('Детали') }} <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="stat-icon-bg opacity-10">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>

        <!-- Average Check -->
        <div class="col-lg-3 col-md-6 mb-3 d-flex">
            <div class="stat-card stat-card-secondary w-100 shadow-sm border-0">
                <div class="stat-card-body p-4">
                    <div class="stat-main-info text-left">
                        <div class="stat-label text-uppercase small font-weight-bold opacity-70 mb-1">{{ __('Средний чек') }}</div>
                        <div class="stat-value h2 font-weight-bold mb-0">
                            {{ number_format($averageOrderValue, 2) }}<span class="small font-weight-normal text-muted ml-1">{{ \App\Models\Option::get('currency') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-footer px-4 py-2 bg-light border-top rounded-bottom">
                    <a href="{{ route('admin.purchases.index') }}" class="stat-link text-secondary small font-weight-bold">
                        {{ __('Детали') }} <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="stat-icon-bg opacity-10">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Графики и аналитика -->
    <div class="row mt-4">
        <div class="col-12 mb-3">
            <h5 class="text-secondary font-weight-bold"><i class="fas fa-chart-bar mr-2 text-muted"></i>{{ __('Аналитика продаж') }}</h5>
        </div>
    </div>

    <div class="row">
        <!-- График продаж за 30 дней -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-chart-line mr-2 text-primary"></i>{{ __('Динамика выручки') }}</h6>
                </div>
                <div class="card-body p-3" style="min-height: 400px; max-height: 400px; position: relative;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Круговой график по категориям -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-chart-pie mr-2 text-info"></i>{{ __('По категориям') }}</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center p-3" style="min-height: 400px; max-height: 400px; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Топ товаров -->
    @if(count($topProducts) > 0)
        <div class="row mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0">
                        <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-fire mr-2 text-danger"></i>{{ __('Топ продаваемых товаров') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 top-products-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-top-0">{{ __('Название') }}</th>
                                        <th class="text-center border-top-0">{{ __('Продано') }}</th>
                                        <th class="text-right border-top-0">{{ __('Выручка') }}</th>
                                        <th class="text-center border-top-0" style="width: 100px;">{{ __('Действие') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProducts as $product)
                                        <tr>
                                            <td class="align-middle">
                                                <a href="{{ route('admin.service-accounts.edit', $product['id']) }}" class="text-dark font-weight-bold">
                                                    {{ $product['title'] }}
                                                </a>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-light border">{{ $product['sold'] }} {{ __('шт.') }}</span>
                                            </td>
                                            <td class="text-right font-weight-bold text-success align-middle">
                                                ${{ number_format($product['revenue'], 2) }}
                                            </td>
                                            <td class="text-center align-middle">
                                                <a href="{{ route('admin.service-accounts.edit', $product['id']) }}" class="btn btn-sm btn-outline-primary shadow-sm">
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
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .stat-card:hover {
        transform: translateY(-3px);
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

        // Данные для тултипов (переданы из контроллера)
        var salesTooltips = {!! json_encode($salesChartData['tooltips']) !!};

        // Тексты для локализации JS
        const LABELS = {
            sales: '{{ __('Продажи') }}',
            sum: '{{ __('Сумма продаж') }}',
            items: '{{ __('Товаров') }}',
            orders: '{{ __('Заказов') }}',
            avg: '{{ __('Ср. чек') }}',
            new: '{{ __('Новых') }}',
            returning: '{{ __('Вернувшихся') }}'
        };

        // График продаж
        if (salesChartElement) {
            const salesCtx = salesChartElement.getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($salesChartData['labels']) !!},
                    datasets: [{
                        label: LABELS.sales,
                        data: {!! json_encode($salesChartData['data']) !!},
                        borderColor: 'rgb(0, 123, 255)',
                        backgroundColor: 'rgba(0, 123, 255, 0.05)',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(0, 123, 255)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleFontColor: '#333',
                        bodyFontColor: '#666',
                        footerFontColor: '#666',
                        borderColor: 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        titleFontSize: 14,
                        bodyFontSize: 13,
                        footerFontSize: 12,
                        cornerRadius: 8,
                        xPadding: 12,
                        yPadding: 12,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return LABELS.sum + ': $' + parseFloat(tooltipItem.yLabel).toFixed(2);
                            },
                            footer: function(tooltipItems, data) {
                                // tooltipItems is an array of items for the hovered index
                                var index = tooltipItems[0].index;
                                var extra = salesTooltips;
                                
                                return [
                                    '', // Spacer
                                    '📦 ' + LABELS.items + ': ' + extra.items[index] + ' шт',
                                    '🧾 ' + LABELS.orders + ': ' + extra.orders[index],
                                    '💲 ' + LABELS.avg + ': $' + extra.avg_check[index],
                                    '👤 ' + LABELS.new + ': ' + extra.new_buyers[index],
                                    '🔄 ' + LABELS.returning + ': ' + extra.returning_buyers[index]
                                ];
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                fontColor: '#999',
                                callback: function(value) {
                                    return '$' + value;
                                }
                            },
                            gridLines: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.03)',
                                drawBorder: false
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                fontColor: '#999',
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        }]
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
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        display: true
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var index = tooltipItem.index;
                                var value = dataset.data[index];
                                var label = data.labels[index];
                                return label + ': ' + value + ' шт.';
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
