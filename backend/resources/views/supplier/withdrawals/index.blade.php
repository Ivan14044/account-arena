@extends('adminlte::page')

@section('title', 'Вывод средств')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Вывод средств</h1>
        <div>
            <a href="{{ route('supplier.dashboard') }}" class="btn btn-secondary">
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <!-- Balance Card -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Доступный баланс</span>
                    <span class="info-box-number">{{ number_format($supplier->supplier_balance, 2) }} $</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Ваша комиссия</span>
                    <span class="info-box-number">{{ $supplier->supplier_commission }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Details Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Реквизиты для вывода</h3>
            <div class="card-tools">
                <a href="{{ route('supplier.withdrawals.payment-details') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> Редактировать реквизиты
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-coins mr-2"></i> TRC-20 кошелек:</strong>
                    <p class="text-muted">
                        {{ $supplier->trc20_wallet ?? 'Не указан' }}
                    </p>
                </div>
                <div class="col-md-6">
                    <strong><i class="fas fa-credit-card mr-2"></i> Карта (грн):</strong>
                    <p class="text-muted">
                        {{ $supplier->card_number_uah ?? 'Не указана' }}
                    </p>
                </div>
            </div>

            @if(!$supplier->trc20_wallet && !$supplier->card_number_uah)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Пожалуйста, укажите хотя бы один способ вывода средств для создания запроса.
                </div>
            @endif

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Если вашего способа оплаты нет в списке, пожалуйста, свяжитесь с администратором: 
                <a href="{{ $telegramSupportLink }}" target="_blank" class="alert-link">
                    <i class="fab fa-telegram"></i> Написать в Telegram
                </a>
            </div>
        </div>
    </div>

    <!-- Create Withdrawal Request Button -->
    @if($supplier->trc20_wallet || $supplier->card_number_uah)
        <div class="mb-3">
            <a href="{{ route('supplier.withdrawals.create') }}" class="btn btn-success btn-lg">
                <i class="fas fa-plus"></i> Создать запрос на вывод
            </a>
        </div>
    @endif

    <!-- Withdrawal Requests History -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">История запросов на вывод</h3>
        </div>
        <div class="card-body p-0">
            @if($withdrawalRequests->count() > 0)
            <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Сумма</th>
                        <th>Способ оплаты</th>
                        <th>Реквизиты</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawalRequests as $request)
                    <tr>
                        <td>{{ $request->id }}</td>
                        <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
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
                            @if($request->status == 'pending')
                                <form action="{{ route('supplier.withdrawals.cancel', $request) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Вы уверены, что хотите отменить этот запрос?')">
                                        <i class="fas fa-times"></i> Отменить
                                    </button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @if($request->admin_comment && ($request->status == 'rejected' || $request->status == 'paid'))
                    <tr class="bg-light">
                        <td colspan="7">
                            <small>
                                <strong>Комментарий администратора:</strong> {{ $request->admin_comment }}
                            </small>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            </div>
            @else
            <div class="p-3 text-center text-muted">
                <p>У вас пока нет запросов на вывод средств</p>
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

