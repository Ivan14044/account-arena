@extends('adminlte::page')

@section('title', 'Панель поставщика')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Панель поставщика</h1>
        <a href="{{ route('supplier.logout') }}" class="btn btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Выход
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
        </div>
        
        <!-- Stats Cards -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($totalRevenue, 2) }} $</h3>
                    <p>Выручка (30 дней)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalOrders }}</h3>
                    <p>Заказов (30 дней)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($averageCheck, 2) }} $</h3>
                    <p>Средний чек</p>
                </div>
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>{{ $activeProducts }}/{{ $totalProducts }}</h3>
                    <p>Активных товаров</p>
                </div>
                <div class="icon">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- График продаж -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-1"></i>
                        График продаж (последние 7 дней)
                    </h3>
                </div>
                <div class="card-body">
                    @if($totalOrders > 0)
                        <canvas id="salesChart" height="100"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <p>Пока нет данных о продажах</p>
                            <small>График появится после первых покупок ваших товаров</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Баланс и уведомления -->
        <div class="col-lg-4">
            <div class="card bg-gradient-info">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fas fa-wallet mr-1"></i>
                        Ваш баланс
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3 pb-3">
                        <p class="text-white mb-0">Доступно:</p>
                        <h2 class="text-white mb-0">{{ number_format($supplier->supplier_balance, 2) }} $</h2>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="text-white mb-0">Комиссия:</p>
                        <p class="text-white mb-0">{{ $supplier->supplier_commission }}%</p>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-sm btn-light btn-block" disabled>
                        <i class="fas fa-money-check-alt mr-1"></i> Вывести средства
                    </button>
                </div>
            </div>

            @if($unreadCount > 0)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell mr-1"></i>
                        Уведомления
                        <span class="badge badge-danger ml-1">{{ $unreadCount }}</span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($unreadNotifications as $notification)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $notification->title }}</strong>
                                    <p class="mb-0 text-muted small">{{ $notification->message }}</p>
                                </div>
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('supplier.notifications.index') }}" class="text-muted">Все уведомления</a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Топ-5 товаров -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy mr-1"></i>
                        Топ-5 самых продаваемых товаров
                    </h3>
                </div>
                <div class="card-body p-0">
                    @if($topProducts->count() > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Название</th>
                                <th>Продано</th>
                                <th>Выручка</th>
                                <th>Остаток</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $product['title'] }}</td>
                                <td>{{ $product['sold'] }}</td>
                                <td>{{ number_format($product['revenue'], 2) }} $</td>
                                <td>
                                    @if($product['stock'] < 10)
                                        <span class="badge badge-warning">{{ $product['stock'] }}</span>
                                    @else
                                        {{ $product['stock'] }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="p-3 text-center text-muted">
                        <p>Пока нет проданных товаров</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Товары с низким остатком -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
                        Товары с низким остатком (<10 шт)
                    </h3>
                </div>
                <div class="card-body p-0">
                    @if(count($lowStockProducts) > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Остаток</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                            <tr>
                                <td>{{ $product->title }}</td>
                                <td>
                                    <span class="badge badge-warning">{{ $product->getAvailableStock() }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('supplier.products.edit', $product) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Пополнить
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="p-3 text-center text-muted">
                        <p>Все товары в наличии</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Быстрые действия</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('supplier.products.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Добавить товар
                    </a>
                    <a href="{{ route('supplier.products.index') }}" class="btn btn-info">
                        <i class="fas fa-list"></i> Мои товары
                    </a>
                    <a href="{{ route('supplier.discounts.index') }}" class="btn btn-warning">
                        <i class="fas fa-percent"></i> Управление скидками
                    </a>
                    <a href="{{ route('supplier.orders.index') }}" class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> Мои заказы
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // График продаж
    const salesData = @json($last7Days);
    const chartElement = document.getElementById('salesChart');
    
    if (!chartElement) {
        return; // No chart element, skip initialization
    }
    
    const ctx = chartElement.getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.date),
            datasets: [{
                label: 'Выручка ($)',
                data: salesData.map(d => d.revenue),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Выручка: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
