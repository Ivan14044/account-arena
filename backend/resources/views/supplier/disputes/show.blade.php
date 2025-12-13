@extends('adminlte::page')

@section('title', 'Претензия #' . $dispute->id)

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Претензия #{{ $dispute->id }}</h1>
        <a href="{{ route('supplier.disputes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        {{-- Основная информация --}}
        <div class="col-md-8 col-12 mb-3 mb-md-0">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о претензии</h3>
                    <div class="card-tools">
                        <span class="badge {{ $dispute->getStatusBadgeClass() }} badge-lg">
                            {{ $dispute->getStatusText() }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3 col-12">Дата создания:</dt>
                        <dd class="col-sm-9 col-12 mb-2">{{ $dispute->created_at->format('d.m.Y H:i:s') }}</dd>

                        <dt class="col-sm-3 col-12">Покупатель:</dt>
                        <dd class="col-sm-9 col-12 mb-2">
                            {{ $dispute->user->name }} ({{ $dispute->user->email }})
                        </dd>

                        <dt class="col-sm-3 col-12">Товар:</dt>
                        <dd class="col-sm-9 col-12 mb-2">
                            @if($dispute->serviceAccount)
                                <strong>{{ $dispute->serviceAccount->title }}</strong><br>
                                <small class="text-muted">Логин: {{ $dispute->serviceAccount->login }}</small>
                            @else
                                <span class="text-muted">Товар удален</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3 col-12">Сумма покупки:</dt>
                        <dd class="col-sm-9 col-12 mb-2">
                            ${{ number_format($dispute->transaction->amount, 2) }}
                            <br>
                            <small class="text-muted">Транзакция #{{ $dispute->transaction->id }} от {{ $dispute->transaction->created_at->format('d.m.Y H:i') }}</small>
                        </dd>

                        <dt class="col-sm-3 col-12">Причина претензии:</dt>
                        <dd class="col-sm-9 col-12 mb-2">
                            <span class="badge badge-secondary">{{ $dispute->getReasonText() }}</span>
                        </dd>

                        <dt class="col-sm-3 col-12">Описание проблемы:</dt>
                        <dd class="col-sm-9 col-12 mb-2">
                            <div class="alert alert-light border">
                                {{ $dispute->customer_description }}
                            </div>
                        </dd>

                        @if($dispute->screenshot_url)
                            <dt class="col-sm-3 col-12">Скриншот:</dt>
                            <dd class="col-sm-9 col-12 mb-2">
                                <div class="mb-2">
                                    <a href="{{ $dispute->screenshot_url }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-image"></i> Посмотреть скриншот
                                    </a>
                                    @if($dispute->screenshot_type === 'upload')
                                        <span class="badge badge-success">Загруженный файл</span>
                                    @else
                                        <span class="badge badge-info">Ссылка на изображение</span>
                                    @endif
                                </div>
                                <div class="border p-2 bg-light">
                                    <img src="{{ $dispute->screenshot_url }}"
                                         alt="Скриншот проблемы"
                                         class="img-fluid"
                                         style="max-height: 400px; cursor: pointer;"
                                         onclick="window.open('{{ $dispute->screenshot_url }}', '_blank')">
                                </div>
                            </dd>
                        @endif

                        @if($dispute->resolved_at)
                            <dt class="col-sm-3 col-12">Дата решения:</dt>
                            <dd class="col-sm-9 col-12 mb-2">{{ $dispute->resolved_at->format('d.m.Y H:i:s') }}</dd>

                            <dt class="col-sm-3 col-12">Решение администратора:</dt>
                            <dd class="col-sm-9 col-12 mb-2">
                                <span class="badge badge-{{ $dispute->admin_decision === 'refund' ? 'danger' : 'info' }} badge-lg">
                                    {{ $dispute->getDecisionText() }}
                                </span>
                            </dd>

                            @if($dispute->refund_amount)
                                <dt class="col-sm-3 col-12">Сумма возврата:</dt>
                                <dd class="col-sm-9 col-12 mb-2">
                                    <span class="text-danger font-weight-bold">
                                        -${{ number_format($dispute->refund_amount, 2) }}
                                    </span>
                                    <br>
                                    <small class="text-muted">Списано с вашего баланса</small>
                                </dd>
                            @endif

                            @if($dispute->admin_comment)
                                <dt class="col-sm-3 col-12">Комментарий администратора:</dt>
                                <dd class="col-sm-9 col-12 mb-2">
                                    <div class="alert alert-info border">
                                        {{ $dispute->admin_comment }}
                                    </div>
                                </dd>
                            @endif

                            @if($dispute->resolver)
                                <dt class="col-sm-3 col-12">Обработал:</dt>
                                <dd class="col-sm-9 col-12 mb-2">{{ $dispute->resolver->name }}</dd>
                            @endif
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Статус и рекомендации --}}
        <div class="col-md-4 col-12">
            <div class="card card-{{ $dispute->status === 'resolved' && $dispute->admin_decision === 'refund' ? 'danger' : ($dispute->status === 'rejected' ? 'success' : 'warning') }}">
                <div class="card-header">
                    <h3 class="card-title">Статус претензии</h3>
                </div>
                <div class="card-body">
                    @if($dispute->status === 'new' || $dispute->status === 'in_review')
                        <div class="alert alert-warning">
                            <i class="fas fa-hourglass-half"></i>
                            Претензия в обработке. Ожидайте решения администратора.
                        </div>
                    @elseif($dispute->status === 'resolved')
                        @if($dispute->admin_decision === 'refund')
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Средства возвращены покупателю и списаны с вашего баланса.
                            </div>
                        @elseif($dispute->admin_decision === 'replacement')
                            <div class="alert alert-info">
                                <i class="fas fa-exchange-alt"></i>
                                Покупателю выдана замена товара.
                            </div>
                        @endif
                    @elseif($dispute->status === 'rejected')
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Претензия отклонена. Ваш товар признан валидным.
                        </div>
                    @endif

                    <p><strong>Текущий статус:</strong> {{ $dispute->getStatusText() }}</p>
                    @if($dispute->resolved_at)
                        <p><strong>Дата решения:</strong> {{ $dispute->resolved_at->format('d.m.Y H:i') }}</p>
                    @endif
                </div>
            </div>

            @if($dispute->status === 'new' || $dispute->status === 'in_review')
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Рекомендации</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i>
                                Проверяйте товары перед загрузкой
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i>
                                Указывайте актуальные данные
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i>
                                Обновляйте товары при необходимости
                            </li>
                        </ul>
                        <hr>
                        <small class="text-muted">
                            Частые претензии могут привести к снижению рейтинга и ограничению функционала.
                        </small>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
<style>
    .badge-lg {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
</style>
@stop

