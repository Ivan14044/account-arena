@extends('adminlte::page')

@section('title', 'Запросы на вывод средств')

@section('content_header')
    <h1>Запросы на вывод средств</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Фильтры</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.withdrawal-requests.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Статус</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Все</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>В обработке</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Одобрен</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Оплачен</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклонен</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="supplier_id">Поставщик</label>
                            <select name="supplier_id" id="supplier_id" class="form-control">
                                <option value="">Все</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">С даты</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">По дату</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Найти
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.withdrawal-requests.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-redo"></i>Сбросить</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Список запросов ({{ $withdrawalRequests->total() }})</h3>
        </div>
        <div class="card-body p-0">
            @if($withdrawalRequests->count() > 0)
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th></th>
                        <th>Поставщик</th>
                        <th>Сумма</th>
                        <th>Способ</th>
                        <th>Реквизиты</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawalRequests as $request)
                    <tr class="{{ $request->status == 'pending' ? 'table-warning' : '' }}">
                        <td>#{{ $request->id }}</td>
                        <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.suppliers.show', $request->supplier) }}">
                                {{ $request->supplier->name }}
                            </a>
                            <br>
                            <small class="text-muted">{{ $request->supplier->email }}</small>
                        </td>
                        <td><strong>{{ number_format($request->amount, 2) }} $</strong></td>
                        <td>
                            @if($request->payment_method == 'trc20')
                                <span class="badge badge-info"><i class="fas fa-coins"></i> TRC-20</span>
                            @else
                                <span class="badge badge-primary"><i class="fas fa-credit-card"></i> Карта грн</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $request->payment_details }}</small>
                        </td>
                        <td>
                            @if($request->status == 'pending')
                                <span class="badge badge-warning">В обработке</span>
                            @elseif($request->status == 'approved')
                                <span class="badge badge-info">Одобрен</span>
                            @elseif($request->status == 'paid')
                                <span class="badge badge-success">Оплачен</span>
                            @else
                                <span class="badge badge-danger">Отклонен</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.withdrawal-requests.show', $request) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-3 text-center text-muted">
                <p>Запросы не найдены</p>
            </div>
            @endif
        </div>
        @if($withdrawalRequests->hasPages())
        <div class="card-footer">
            {{ $withdrawalRequests->links() }}
        </div>
        @endif
    </div>
@endsection


