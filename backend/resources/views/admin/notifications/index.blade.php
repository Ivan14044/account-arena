@extends('adminlte::page')

@section('title', 'Уведомления')

@section('content_header')
    <h1>Уведомления</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Список уведомлений</h3>
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-warning float-right">Массовое уведомление</a>
            </div>
            <div class="card-body">
                <table id="notification-templates-table" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th style="width: 40px">ID</th>
                        <th>Пользователь</th>
                        <th>Уведомление</th>
                        <th>Прочитано</th>
                        <th>Создано</th>
                        <th style="width: 60px">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($notifications as $notification)
                        <tr>
                            <td>{{ $notification->id }}</td>
                            <td>
                                <a href="{{ route('admin.users.edit', $notification->user) }}" target="_blank">
                                    {{ $notification->user->email }}
                                </a>
                            </td>
                            <td>
                                @if($notification->template)
                                    <a href="{{ route('admin.notification-templates.edit', $notification->template) }}" target="_blank">
                                        {{ $notification->template->name }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                {{ $notification->read_at ? 'Да' : 'Нет' }}
                            </td>
                            <td data-order="{{ strtotime($notification->created_at) }}">
                                {{ \Carbon\Carbon::parse($notification->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $notification->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <div class="modal fade" id="deleteModal{{ $notification->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                Вы уверены, что хотите удалить это уведомление?
                                            </div>
                                            <div class="modal-footer">
                                                <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Да, удалить</button>
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
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
        $('#notification-templates-table').DataTable({
            "order": [[0, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": 3 }
            ]
        });
    });
</script>
@endsection
