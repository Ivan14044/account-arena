@extends('adminlte::page')

@section('title', 'Аналитика ручной обработки заказов')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Аналитика ручной обработки заказов
                </h1>
                <p class="text-muted mb-0 mt-1">Расширенная статистика по обработке заказов</p>
            </div>
            <a href="{{ route('admin.manual-delivery.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Назад к списку
            </a>
        </div>
    </div>
@stop

@section('content')
    <!-- Фильтр по датам -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.manual-delivery.analytics') }}" class="row">
                <div class="col-md-4">
                    <label>Дата начала</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-4">
                    <label>Дата окончания</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter mr-2"></i>Применить фильтр
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Общая статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Заказов в обработке</div>
                        <div class="stat-value">{{ $totalOrders }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Обработано за период</div>
                        <div class="stat-value">{{ $completedOrders }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Среднее время (часов)</div>
                        <div class="stat-value">{{ number_format($avgProcessingTime ?? 0, 1) }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-danger">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ожидают товара</div>
                        <div class="stat-value">{{ $waitingStockCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Детальная статистика -->
    <div class="row">
        <!-- Статистика по времени обработки -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Время обработки
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Показатель</th>
                                <th>Значение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Среднее время</td>
                                <td><strong>{{ number_format($avgProcessingTime ?? 0, 2) }} ч.</strong></td>
                            </tr>
                            <tr>
                                <td>Минимальное время</td>
                                <td><strong>{{ $minProcessingTime ?? 0 }} ч.</strong></td>
                            </tr>
                            <tr>
                                <td>Максимальное время</td>
                                <td><strong>{{ $maxProcessingTime ?? 0 }} ч.</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Статистика по дням недели -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-week mr-2"></i>
                        Обработка по дням недели
                    </h3>
                </div>
                <div class="card-body">
                    @if($ordersByDay->isEmpty())
                        <p class="text-muted">Нет данных за выбранный период</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>День недели</th>
                                    <th>Количество заказов</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordersByDay as $day)
                                    <tr>
                                        <td>{{ $day->day_name }}</td>
                                        <td><strong>{{ $day->count }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <!-- Статистика по менеджерам -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-2"></i>
                        Статистика по менеджерам
                    </h3>
                </div>
                <div class="card-body">
                    @if($ordersByManager->isEmpty())
                        <p class="text-muted">Нет данных за выбранный период</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Менеджер</th>
                                    <th>Заказов</th>
                                    <th>Среднее время</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordersByManager as $manager)
                                    <tr>
                                        <td>
                                            <div>{{ $manager['manager_name'] }}</div>
                                            <small class="text-muted">{{ $manager['manager_email'] }}</small>
                                        </td>
                                        <td><strong>{{ $manager['orders_count'] }}</strong></td>
                                        <td><strong>{{ number_format($manager['avg_processing_time'] ?? 0, 2) }} ч.</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <!-- Статистика по типам выдачи -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tags mr-2"></i>
                        Статистика по типам выдачи
                    </h3>
                </div>
                <div class="card-body">
                    @if($ordersByDeliveryType->isEmpty())
                        <p class="text-muted">Нет данных за выбранный период</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Тип выдачи</th>
                                    <th>Заказов</th>
                                    <th>Среднее время</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordersByDeliveryType as $type)
                                    <tr>
                                        <td>
                                            @if($type['delivery_type'] === 'manual')
                                                <span class="badge badge-warning">Ручная</span>
                                            @elseif($type['delivery_type'] === 'automatic')
                                                <span class="badge badge-success">Автоматическая</span>
                                            @else
                                                <span class="badge badge-secondary">Неизвестно</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $type['orders_count'] }}</strong></td>
                                        <td><strong>{{ number_format($type['avg_processing_time'] ?? 0, 2) }} ч.</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <!-- Дополнительная статистика -->
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Проблемные заказы
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-box-open"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Ожидают товара</span>
                                    <span class="info-box-number">{{ $waitingStockCount }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Заказы с ошибками</span>
                                    <span class="info-box-number">{{ $errorOrdersCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @include('admin.layouts.modern-styles')
@endsection
