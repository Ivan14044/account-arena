@extends('adminlte::page')

@section('title', 'Мои заказы')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Мои заказы</h1>
        <div>
            <a href="{{ route('supplier.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-home"></i> Панель
            </a>
            <a href="{{ route('supplier.logout') }}" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Выход
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Всего заказов</span>
                    <span class="info-box-number">{{ $totalOrders }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Общая выручка</span>
                    <span class="info-box-number">{{ number_format($totalRevenue, 2) }} $</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Фильтры</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('supplier.orders.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="product_id">Товар</label>
                            <select name="product_id" id="product_id" class="form-control">
                                <option value="">Все товары</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">Дата от</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">Дата до</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Поиск</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Покупатель или товар..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Применить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @if(request()->hasAny(['product_id', 'date_from', 'date_to', 'search']))
                    <div class="row">
                        <div class="col-12">
                            <a href="{{ route('supplier.orders.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times"></i> Сбросить фильтры
                            </a>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Список заказов</h3>
        </div>
        <div class="card-body p-0">
            @if($orders->count() > 0)
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Товар</th>
                        <th>Покупатель</th>
                        <th>Сумма</th>
                        <th>Способ оплаты</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($order->serviceAccount)
                                {{ $order->serviceAccount->title }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($order->user)
                                {{ $order->user->name }}
                                <br>
                                <small class="text-muted">{{ $order->user->email }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><strong>{{ number_format($order->amount, 2) }} $</strong></td>
                        <td>
                            @if($order->payment_method)
                                <span class="badge badge-info">{{ ucfirst($order->payment_method) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-4 text-center text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Заказы не найдены</p>
                @if(request()->hasAny(['product_id', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('supplier.orders.index') }}" class="btn btn-sm btn-primary">
                        Сбросить фильтры
                    </a>
                @endif
            </div>
            @endif
        </div>
        @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
@endsection

