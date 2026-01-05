@extends('adminlte::page')

@section('title', 'Поставщики')

@section('content_header')
    <h1></h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"></h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.suppliers.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Поиск по имени или email"
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary flex-fill mr-2">
                                <i class="fas fa-search"></i> <span class="d-none d-sm-inline">Поиск</span>
                            </button>
                            @if(request('search'))
                            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> <span class="d-none d-sm-inline">Сбросить</span>
                                <span class="d-sm-none">({{ $suppliers->total() }})</span>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Поставщики ({{ $suppliers->total() }})</h3>
            <div class="card-tools">
                <a href="{{ route('admin.suppliers.settings') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog"></i> <span class="d-none d-sm-inline">Настройки</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($suppliers->count() > 0)
            <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Рейтинг</th>
                        <th>Баланс</th>
                        <th>Комиссия</th>
                        <th>TRC-20</th>
                        <th>Карта грн</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suppliers as $supplier)
                    <tr>
                        <td data-label="ID">{{ $supplier->id }}</td>
                        <td data-label="Имя">{{ $supplier->name }}</td>
                        <td data-label="Email"><small>{{ $supplier->email }}</small></td>
                        <td data-label="Рейтинг">
                            @php
                                $rating = $supplier->supplier_rating ?? 100;
                                $level = $supplier->getRatingLevel();
                            @endphp
                            <div class="text-center">
                                <span class="badge badge-{{ $level['class'] }} badge-lg">
                                    {{ $level['icon'] }} {{ $rating }}%
                                </span>
                                <br>
                                <small class="text-muted">
                                    @for($i = 0; $i < $level['stars']; $i++)
                                        ⭐
                                    @endfor
                                </small>
                            </div>
                        </td>
                        <td data-label="Баланс"><strong>{{ number_format($supplier->supplier_balance, 2) }} $</strong></td>
                        <td data-label="Комиссия">{{ $supplier->supplier_commission }}%</td>
                        <td data-label="TRC-20">
                            @if($supplier->trc20_wallet)
                                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                            @else
                                <span class="badge badge-secondary">—</span>
                            @endif
                        </td>
                        <td data-label="Карта грн">
                            @if($supplier->card_number_uah)
                                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                            @else
                                <span class="badge badge-secondary">—</span>
                            @endif
                        </td>
                        <td data-label="Действия">
                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> <span class="d-none d-sm-inline">Просмотр</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @else
            <div class="p-3 text-center text-muted">
                <p>Поставщики не найдены</p>
            </div>
            @endif
        </div>
        @if($suppliers->hasPages())
        <div class="card-footer">
            {{ $suppliers->links() }}
        </div>
        @endif
    </div>
@endsection

