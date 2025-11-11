@extends('adminlte::page')

@section('title', 'Запрос на вывод #' . $withdrawalRequest->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Запрос на вывод #{{ $withdrawalRequest->id }}</h1>
        <a href="{{ route('admin.withdrawal-requests.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>Назад</a>
    </div>
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

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о запросе</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">ID запроса:</dt>
                        <dd class="col-sm-8">#{{ $withdrawalRequest->id }}</dd>

                        <dt class="col-sm-4">Дата создания:</dt>
                        <dd class="col-sm-8">{{ $withdrawalRequest->created_at->format('d.m.Y H:i') }}</dd>

                        <dt class="col-sm-4">Сумма:</dt>
                        <dd class="col-sm-8"><strong class="text-success">{{ number_format($withdrawalRequest->amount, 2) }} $</strong></dd>

                        <dt class="col-sm-4">Способ вывода:</dt>
                        <dd class="col-sm-8">
                            @if($withdrawalRequest->payment_method == 'trc20')
                                <span class="badge badge-info"><i class="fas fa-coins"></i> TRC-20</span>
                            @else
                                <span class="badge badge-primary"><i class="fas fa-credit-card"></i> Карта грн</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Реквизиты:</dt>
                        <dd class="col-sm-8">
                            <code>{{ $withdrawalRequest->payment_details }}</code>
                        </dd>

                        <dt class="col-sm-4">Статус:</dt>
                        <dd class="col-sm-8">
                            @if($withdrawalRequest->status == 'pending')
                                <span class="badge badge-warning">В обработке</span>
                            @elseif($withdrawalRequest->status == 'approved')
                                <span class="badge badge-info">Одобрен</span>
                            @elseif($withdrawalRequest->status == 'paid')
                                <span class="badge badge-success">Оплачен</span>
                            @else
                                <span class="badge badge-danger">Отклонен</span>
                            @endif
                        </dd>

                        @if($withdrawalRequest->processed_at)
                        <dt class="col-sm-4">Обработан:</dt>
                        <dd class="col-sm-8">{{ $withdrawalRequest->processed_at->format('d.m.Y H:i') }}</dd>
                        @endif

                        @if($withdrawalRequest->admin_comment)
                        <dt class="col-sm-4">Комментарий:</dt>
                        <dd class="col-sm-8">{{ $withdrawalRequest->admin_comment }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о поставщике</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">ID:</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.suppliers.show', $withdrawalRequest->supplier) }}">
                                #{{ $withdrawalRequest->supplier->id }}
                            </a>
                        </dd>

                        <dt class="col-sm-4">Имя:</dt>
                        <dd class="col-sm-8">{{ $withdrawalRequest->supplier->name }}</dd>

                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8">{{ $withdrawalRequest->supplier->email }}</dd>

                        <dt class="col-sm-4">Баланс:</dt>
                        <dd class="col-sm-8">
                            <strong>{{ number_format($withdrawalRequest->supplier->supplier_balance, 2) }} $</strong>
                        </dd>

                        <dt class="col-sm-4">Комиссия:</dt>
                        <dd class="col-sm-8">{{ $withdrawalRequest->supplier->supplier_commission }}%</dd>
                    </dl>

                    <a href="{{ route('admin.suppliers.show', $withdrawalRequest->supplier) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-user"></i> Профиль поставщика
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Действия</h3>
        </div>
        <div class="card-body">
            @if($withdrawalRequest->status == 'pending')
                <div class="row">
                    <div class="col-md-6">
                        <form action="{{ route('admin.withdrawal-requests.approve', $withdrawalRequest) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg btn-block" 
                                    onclick="return confirm('Одобрить этот запрос на вывод?')">
                                <i class="fas fa-check"></i> Одобрить
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-danger btn-lg btn-block" data-toggle="modal" data-target="#rejectModal">
                            <i class="fas fa-times"></i> Отклонить
                        </button>
                    </div>
                </div>
            @elseif($withdrawalRequest->status == 'approved')
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success btn-lg btn-block" data-toggle="modal" data-target="#paidModal">
                            <i class="fas fa-money-bill-wave"></i> Отметить как оплачено
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-danger btn-lg btn-block" data-toggle="modal" data-target="#rejectModal">
                            <i class="fas fa-times"></i> Отклонить
                        </button>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Этот запрос уже обработан. Статус: <strong>{{ $withdrawalRequest->status }}</strong>
                </div>
            @endif
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.withdrawal-requests.reject', $withdrawalRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title">Отклонить запрос</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="admin_comment">Причина отклонения *</label>
                            <textarea name="admin_comment" 
                                      id="admin_comment" 
                                      class="form-control" 
                                      rows="3" 
                                      required
                                      placeholder="Укажите причину отклонения"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">Отклонить запрос</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Paid Modal -->
    <div class="modal fade" id="paidModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.withdrawal-requests.mark-paid', $withdrawalRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title">Отметить как оплачено</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Внимание!</strong> После подтверждения с баланса поставщика будет списано <strong>{{ number_format($withdrawalRequest->amount, 2) }} $</strong>
                        </div>
                        <div class="form-group">
                            <label for="admin_comment_paid">Комментарий (необязательно)</label>
                            <textarea name="admin_comment" 
                                      id="admin_comment_paid" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Добавьте комментарий (например, номер транзакции)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success">Подтвердить оплату</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection




