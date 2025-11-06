@extends('adminlte::page')

@section('title', 'Поставщик: ' . $supplier->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Поставщик: {{ $supplier->name }}</h1>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>Назад</a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о поставщике</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">ID:</dt>
                        <dd class="col-sm-8">{{ $supplier->id }}</dd>

                        <dt class="col-sm-4">Имя:</dt>
                        <dd class="col-sm-8">{{ $supplier->name }}</dd>

                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8">{{ $supplier->email }}</dd>

                        <dt class="col-sm-4">Баланс:</dt>
                        <dd class="col-sm-8"><strong>{{ number_format($supplier->supplier_balance, 2) }} $</strong></dd>

                        <dt class="col-sm-4">Комиссия:</dt>
                        <dd class="col-sm-8">{{ $supplier->supplier_commission }}%</dd>

                        <dt class="col-sm-4">Регистрация:</dt>
                        <dd class="col-sm-8">{{ $supplier->created_at->format('d.m.Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Реквизиты для вывода</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4"><i class="fas fa-coins"></i> TRC-20:</dt>
                        <dd class="col-sm-8">{{ $supplier->trc20_wallet ?? 'Не указан' }}</dd>

                        <dt class="col-sm-4"><i class="fas fa-credit-card"></i> Карта грн:</dt>
                        <dd class="col-sm-8">{{ $supplier->card_number_uah ?? 'Не указана' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Товары поставщика ({{ $supplier->supplierProducts->count() }})</h3>
        </div>
        <div class="card-body p-0">
            @if($supplier->supplierProducts->count() > 0)
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Активен</th>
                        <th>Создан</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supplier->supplierProducts as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->title }}</td>
                        <td>{{ number_format($product->price, 2) }} $</td>
                        <td>
                            @if($product->is_active)
                                <span class="badge badge-success">Да</span>
                            @else
                                <span class="badge badge-danger">Нет</span>
                            @endif
                        </td>
                        <td>{{ $product->created_at->format('d.m.Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-3 text-center text-muted">
                У поставщика пока нет товаров
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">История запросов на вывод ({{ $supplier->withdrawalRequests->count() }})</h3>
        </div>
        <div class="card-body p-0">
            @if($supplier->withdrawalRequests->count() > 0)
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th></th>
                        <th>Сумма</th>
                        <th>Способ</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supplier->withdrawalRequests()->latest()->take(10)->get() as $request)
                    <tr>
                        <td>
                            <a href="{{ route('admin.withdrawal-requests.show', $request) }}">
                                #{{ $request->id }}
                            </a>
                        </td>
                        <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                        <td><strong>{{ number_format($request->amount, 2) }} $</strong></td>
                        <td>
                            @if($request->payment_method == 'trc20')
                                <span class="badge badge-info">TRC-20</span>
                            @else
                                <span class="badge badge-primary">Карта грн</span>
                            @endif
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($supplier->withdrawalRequests->count() > 10)
                <div class="card-footer text-center">
                    <a href="{{ route('admin.withdrawal-requests.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-primary">
                        Показать все запросы
                    </a>
                </div>
            @endif
            @else
            <div class="p-3 text-center text-muted">
                У поставщика нет запросов на вывод
            </div>
            @endif
        </div>
    </div>
@endsection

