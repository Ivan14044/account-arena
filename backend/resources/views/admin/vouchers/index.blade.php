@extends('adminlte::page')

@section('title', 'Ваучеры')

@section('content_header')
    <h1>Ваучеры</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Список ваучеров</h3>
                    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary float-right">+ Добавить</a>
                </div>
                <div class="card-body">
                    <table id="vouchers-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 60px">ID</th>
                            <th>Код</th>
                            <th>Сумма</th>
                            <th>Валюта</th>
                            <th>Пользователь</th>
                            <th>Использован</th>
                            <th>Статус</th>
                            <th style="width: 120px">Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($vouchers as $voucher)
                            <tr>
                                <td>{{ $voucher->id }}</td>
                                <td><code>{{ $voucher->code }}</code></td>
                                <td><strong class="text-success">{{ number_format($voucher->amount, 2) }}</strong></td>
                                <td>{{ $voucher->currency }}</td>
                                <td>
                                    @if($voucher->user)
                                        <a href="{{ route('admin.users.edit', $voucher->user) }}">
                                            {{ $voucher->user->name }} ({{ $voucher->user->email }})
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($voucher->used_at)
                                        <span class="badge badge-success">{{ $voucher->used_at->format('Y-m-d H:i') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($voucher->isUsed())
                                        <span class="badge badge-secondary">Использован</span>
                                    @elseif($voucher->is_active)
                                        <span class="badge badge-success">Активен</span>
                                    @else
                                        <span class="badge badge-warning">Неактивен</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.vouchers.edit', $voucher) }}"
                                       class="btn btn-sm btn-warning" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" data-toggle="modal"
                                            data-target="#deleteModal{{ $voucher->id }}" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <div class="modal fade" id="deleteModal{{ $voucher->id }}" tabindex="-1"
                                         role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Вы уверены, что хотите удалить ваучер <code>{{ $voucher->code }}</code>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                                    <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST"
                                                          style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Удалить</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#vouchers-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.3/i18n/ru.json"
                },
                "columnDefs": [
                    {"orderable": false, "targets": [8]}
                ]
            });
        });
    </script>
@endsection

