@extends('adminlte::page')

@section('title', 'Модерация товаров')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Модерация товаров поставщиков
                </h1>
                <p class="text-muted mb-0 mt-1">Одобрение и отклонение товаров, созданных поставщиками</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-modern alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0 font-weight-normal">
                <i class="fas fa-clock mr-2 text-warning"></i>Товары на модерации ({{ $products->count() }})
            </h5>
        </div>
        <div class="card-body-modern">
            @if($products->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Нет товаров на модерации</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Поставщик</th>
                                <th>Цена</th>
                                <th>Аккаунтов</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>#{{ $product->id }}</td>
                                    <td>
                                        <strong>{{ $product->title }}</strong>
                                        @if($product->category)
                                            <br><small class="text-muted">{{ $product->category->admin_name ?? 'Категория #' . $product->category->id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->supplier)
                                            <a href="{{ route('admin.users.edit', $product->supplier) }}" class="text-primary">
                                                <i class="fas fa-user-tag"></i> {{ $product->supplier->name }}
                                            </a>
                                            <br><small class="text-muted">{{ $product->supplier->email }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>${{ number_format($product->price, 2) }}</strong>
                                        @if($product->hasActiveDiscount())
                                            <br><small class="text-success">Скидка: {{ $product->discount_percent }}%</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $accountsData = is_array($product->accounts_data) ? $product->accounts_data : [];
                                            $totalAccounts = count($accountsData);
                                            $availableAccounts = max(0, $totalAccounts - ($product->used ?? 0));
                                        @endphp
                                        <span class="badge badge-info">{{ $availableAccounts }} / {{ $totalAccounts }}</span>
                                    </td>
                                    <td>
                                        {{ $product->created_at->translatedFormat('d.m.Y H:i') }}
                                        <br><small class="text-muted">{{ $product->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.product-moderation.show', $product) }}" class="btn btn-sm btn-info" title="{{ __('Просмотр') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('admin.product-moderation.approve', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Одобрить этот товар?') }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="{{ __('Одобрить') }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal{{ $product->id }}" title="{{ __('Отклонить') }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <!-- Modal для отклонения -->
                                        <div class="modal fade" id="rejectModal{{ $product->id }}" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form action="{{ route('admin.product-moderation.reject', $product) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ __('Отклонить товар') }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>{{ __('Вы уверены, что хотите отклонить товар') }} <strong>"{{ $product->title }}"</strong>?</p>
                                                            <div class="form-group">
                                                                <label for="moderation_comment{{ $product->id }}">{{ __('Причина отклонения') }} <span class="text-danger">*</span></label>
                                                                <textarea name="moderation_comment" id="moderation_comment{{ $product->id }}" class="form-control" rows="3" required maxlength="1000" placeholder="{{ __('Укажите причину отклонения товара...') }}"></textarea>
                                                                <small class="form-text text-muted">{{ __('Этот комментарий будет отправлен поставщику.') }}</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Отмена') }}</button>
                                                            <button type="submit" class="btn btn-danger">{{ __('Отклонить') }}</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@stop
