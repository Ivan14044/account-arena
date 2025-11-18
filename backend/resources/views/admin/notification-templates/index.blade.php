@extends('adminlte::page')

@section('title', 'Шаблоны уведомлений')

@section('content_header')
    <h1>Шаблоны уведомлений</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header no-border border-0 p-0">
                    <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ request('type') !== 'custom' ? 'active' : '' }}"
                               href="{{ route('admin.notification-templates.index') }}">
                                Системные
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('type') === 'custom' ? 'active' : '' }}"
                               href="{{ route('admin.notification-templates.index', ['type' => 'custom']) }}">
                                Пользовательские
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    @if(request('type') === 'custom')
                        <div class="mb-3">
                            <a href="{{ route('admin.notification-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Создать шаблон
                            </a>
                        </div>
                    @endif
                    <table id="notification-templates-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th>Название</th>
                            <th style="width: 110px">Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($notificationTemplates as $notificationTemplate)
                            <tr>
                                <td>{{ $notificationTemplate->id }}</td>
                                <td>{{ $notificationTemplate->name }}</td>
                                <td>
                                    <a href="{{ route('admin.notification-templates.edit', $notificationTemplate) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if(request('type') === 'custom')
                                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $notificationTemplate->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                        <div class="modal fade" id="deleteModal{{ $notificationTemplate->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Это действие навсегда удалит шаблон уведомления и все связанные уведомления.
                                                        Вы уверены, что хотите продолжить?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="{{ route('admin.notification-templates.destroy', $notificationTemplate) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Да, удалить</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
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
            $('#notification-templates-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 2 }
                ]
            });
        });
    </script>
@endsection
