@extends('adminlte::page')

@section('title', 'Notification templates')

@section('content_header')
    <h1>Notification templates</h1>
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
                                System
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('type') === 'custom' ? 'active' : '' }}"
                               href="{{ route('admin.notification-templates.index', ['type' => 'custom']) }}">
                                Custom
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <table id="notification-templates-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th>Name</th>
                            <th style="width: 110px">Action</th>
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
                                                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        This will permanently delete the notification template and all related notifications.
                                                        Are you sure you want to continue?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="{{ route('admin.notification-templates.destroy', $notificationTemplate) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
