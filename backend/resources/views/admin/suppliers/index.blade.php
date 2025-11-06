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
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder=""
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 
                        </button>
                        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"> ({{ $suppliers->total() }})</h3>
            <div class="card-tools">
                <a href="{{ route('admin.suppliers.settings') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog"></i>Настройки</a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($suppliers->count() > 0)
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
                        <td>{{ $supplier->id }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->email }}</td>
                        <td>
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
                        <td><strong>{{ number_format($supplier->supplier_balance, 2) }} $</strong></td>
                        <td>{{ $supplier->supplier_commission }}%</td>
                        <td>
                            @if($supplier->trc20_wallet)
                                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                            @else
                                <span class="badge badge-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if($supplier->card_number_uah)
                                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                            @else
                                <span class="badge badge-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Просмотр
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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

