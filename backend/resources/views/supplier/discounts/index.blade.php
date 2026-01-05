@extends('adminlte::page')

@section('title', 'Управление скидками')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Управление скидками</h1>
        <div class="d-flex flex-column flex-sm-row w-100 w-md-auto">
            <a href="{{ route('supplier.discounts.create') }}" class="btn btn-primary mb-2 mb-sm-0 mr-sm-2">
                <i class="fas fa-plus"></i> Добавить скидку
            </a>
            <a href="{{ route('supplier.dashboard') }}" class="btn btn-secondary mb-2 mb-sm-0">
                <i class="fas fa-home"></i> Панель
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Активные скидки</h3>
        </div>
        <div class="card-body p-0">
            @if($products->count() > 0)
            <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Цена</th>
                        <th>Скидка</th>
                        <th>Цена со скидкой</th>
                        <th>Период действия</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->title }}</td>
                        <td>{{ number_format($product->price, 2) }} $</td>
                        <td>
                            <span class="badge badge-danger">{{ $product->discount_percent }}%</span>
                        </td>
                        <td>
                            <strong>{{ number_format($product->getCurrentPrice(), 2) }} $</strong>
                        </td>
                        <td>
                            @if($product->discount_start_date || $product->discount_end_date)
                                @if($product->discount_start_date)
                                    с {{ $product->discount_start_date->format('d.m.Y') }}
                                @endif
                                @if($product->discount_end_date)
                                    <br>до {{ $product->discount_end_date->format('d.m.Y') }}
                                @endif
                            @else
                                <span class="text-muted">Бессрочно</span>
                            @endif
                        </td>
                        <td>
                            @if($product->hasActiveDiscount())
                                <span class="badge badge-success">Активна</span>
                            @else
                                <span class="badge badge-secondary">Неактивна</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group-vertical btn-group-sm d-sm-inline-flex" role="group">
                                <a href="{{ route('supplier.discounts.edit', $product) }}" class="btn btn-info mb-1 mb-sm-0">
                                    <i class="fas fa-edit"></i> <span class="d-sm-none">Редактировать</span>
                                </a>
                                <form action="{{ route('supplier.discounts.destroy', $product) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Удалить скидку?')">
                                        <i class="fas fa-trash"></i> <span class="d-sm-none">Удалить</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @else
            <div class="p-4 text-center text-muted">
                <i class="fas fa-percent fa-3x mb-3"></i>
                <p>У вас пока нет активных скидок</p>
                <a href="{{ route('supplier.discounts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить скидку
                </a>
            </div>
            @endif
        </div>
    </div>
@endsection

