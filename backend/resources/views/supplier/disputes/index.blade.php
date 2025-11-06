@extends('adminlte::page')

@section('title', 'Претензии на товары')

@section('content_header')
    <h1>Претензии на мои товары</h1>
@stop

@section('content')
    {{-- Статистика --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Всего претензий</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['new'] + $stats['in_review'] }}</h3>
                    <p>В обработке</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['resolved'] }}</h3>
                    <p>Решенных</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>${{ number_format($stats['total_refunded'], 2) }}</h3>
                    <p>Возвращено средств</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Информация --}}
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Информация:</strong> Здесь отображаются претензии покупателей на ваши товары. 
        Администратор рассматривает каждую претензию и принимает решение. 
        В случае возврата средств, сумма будет списана с вашего баланса.
    </div>

    {{-- Фильтры --}}
    <div class="card collapsed-card">
        <div class="card-header">
            <h3 class="card-title">Фильтры</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body" style="display: none;">
            <form action="{{ route('supplier.disputes.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Статус:</label>
                    <select name="status" class="form-control">
                        <option value="">Все</option>
                        <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Новые</option>
                        <option value="in_review" {{ request('status') == 'in_review' ? 'selected' : '' }}>На рассмотрении</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Решенные</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклоненные</option>
                    </select>
                </div>

                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Дата с:</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Дата по:</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <button type="submit" class="btn btn-primary mr-2 mb-2">
                    <i class="fas fa-search"></i> Применить
                </button>
                <a href="{{ route('supplier.disputes.index') }}" class="btn btn-secondary mb-2">
                    <i class="fas fa-redo"></i> Сбросить
                </a>
            </form>
        </div>
    </div>

    {{-- Таблица претензий --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Список претензий</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Покупатель</th>
                        <th>Товар</th>
                        <th>Причина</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Решение</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $dispute)
                        <tr>
                            <td>#{{ $dispute->id }}</td>
                            <td>{{ $dispute->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                {{ $dispute->user->name }}
                                <br>
                                <small class="text-muted">{{ $dispute->user->email }}</small>
                            </td>
                            <td>
                                @if($dispute->serviceAccount)
                                    {{ $dispute->serviceAccount->title }}
                                    <br>
                                    <small class="text-muted">{{ $dispute->serviceAccount->login }}</small>
                                @else
                                    <span class="text-muted">Товар удален</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ $dispute->getReasonText() }}
                                </span>
                            </td>
                            <td>${{ number_format($dispute->transaction->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $dispute->getStatusBadgeClass() }}">
                                    {{ $dispute->getStatusText() }}
                                </span>
                            </td>
                            <td>
                                @if($dispute->admin_decision)
                                    <span class="badge badge-info">
                                        {{ $dispute->getDecisionText() }}
                                    </span>
                                    @if($dispute->refund_amount)
                                        <br>
                                        <small class="text-danger">-${{ number_format($dispute->refund_amount, 2) }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('supplier.disputes.show', $dispute) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Претензий пока нет
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($disputes->hasPages())
            <div class="card-footer clearfix">
                {{ $disputes->links() }}
            </div>
        @endif
    </div>
@stop


