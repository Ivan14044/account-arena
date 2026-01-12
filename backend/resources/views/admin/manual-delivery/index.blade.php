@extends('adminlte::page')

@section('title', 'Ручная обработка заказов')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Ручная обработка заказов
                </h1>
                <p class="text-muted mb-0 mt-1">Заказы, требующие ручной выдачи товара</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $statistics['pending'] }}</h3>
                    <p>Ожидают обработки</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $statistics['processed_today'] }}</h3>
                    <p>Обработано сегодня</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $statistics['processed_this_week'] }}</h3>
                    <p>Обработано на этой неделе</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $statistics['average_processing_time'] ?? 'N/A' }}</h3>
                    <p>Среднее время (часов)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Список заказов -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list mr-2"></i>
                Заказы на обработку ({{ $pendingOrders->count() }})
            </h3>
        </div>
        <div class="card-body p-0">
            @if($pendingOrders->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Нет заказов, ожидающих обработки</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Номер заказа</th>
                                <th>Покупатель</th>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingOrders as $order)
                                <tr>
                                    <td>
                                        <strong>#{{ $order->order_number }}</strong>
                                    </td>
                                    <td>
                                        @if($order->user)
                                            <div>{{ $order->user->name }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        @else
                                            <div>Гость</div>
                                            <small class="text-muted">{{ $order->guest_email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $order->serviceAccount->title ?? 'Товар удален' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $order->quantity }} шт.</span>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($order->total_amount, 2) }} {{ $order->transaction->currency ?? 'USD' }}</strong>
                                    </td>
                                    <td>
                                        {{ $order->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.manual-delivery.show', $order) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit mr-1"></i>
                                            Обработать
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@stop
