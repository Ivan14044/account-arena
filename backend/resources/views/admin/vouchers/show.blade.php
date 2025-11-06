@extends('adminlte::page')

@section('title', 'Ваучер #' . $voucher->id)

@section('content_header')
    <h1>Ваучер #{{ $voucher->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о ваучере</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 150px">Код</th>
                            <td><code>{{ $voucher->code }}</code></td>
                        </tr>
                        <tr>
                            <th>Сумма</th>
                            <td><strong class="text-success">{{ number_format($voucher->amount, 2) }} {{ $voucher->currency }}</strong></td>
                        </tr>
                        <tr>
                            <th>Статус</th>
                            <td>
                                @if($voucher->isUsed())
                                    <span class="badge badge-secondary">Использован</span>
                                @elseif($voucher->is_active)
                                    <span class="badge badge-success">Активен</span>
                                @else
                                    <span class="badge badge-warning"></span>
                                @endif
                            </td>
                        </tr>
                        @if($voucher->user)
                        <tr>
                            <th>Использован</th>
                            <td>
                                <a href="{{ route('admin.users.edit', $voucher->user) }}">
                                    {{ $voucher->user->name }} ({{ $voucher->user->email }})
                                </a><br>
                                <small class="text-muted">{{ $voucher->used_at->format('Y-m-d H:i:s') }}</small>
                            </td>
                        </tr>
                        @endif
                        @if($voucher->note)
                        <tr>
                            <th>Примечание</th>
                            <td>{{ $voucher->note }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Создан</th>
                            <td>{{ $voucher->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>

                    <div class="mt-3">
                        <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i>Редактировать</a>
                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>Назад</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

