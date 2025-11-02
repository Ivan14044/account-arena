@extends('adminlte::page')

@section('title', 'Notifications')

@section('content_header')
    <h1>Notifications</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">Notifications list</h3>
                    <form action="{{ route('admin.admin_notifications.read-all') }}" method="POST" class="ml-auto">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-check-double mr-1"></i> Mark all as read
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <table id="notifications-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th>Type</th>
                            <th>Text</th>
                            <th>Is read</th>
                            <th>Created at</th>
                            <th style="width: 100px">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($notifications as $notification)
                            <tr @if(!$notification->read) class="bg-light-primary" @endif>
                                <td>{{ $notification->id }}</td>
                                <td>{{ $notification->type }}</td>
                                <td>
                                    <div><strong>{{ $notification->title }}</strong></div>
                                    <div class="text-muted">{{ $notification->message }}</div>
                                </td>
                                <td>
                                    {{ $notification->read ? 'Yes' : 'No' }}
                                </td>
                                <td data-order="{{ strtotime($notification->created_at) }}">
                                    {{ \Carbon\Carbon::parse($notification->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    @if(!$notification->read)
                                        <a href="{{ route('admin.admin_notifications.read', $notification->id) }}"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    @endif

                                    <button class="btn btn-sm btn-danger" data-toggle="modal"
                                            data-target="#deleteModal{{ $notification->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <!-- Modal -->
                                    <div class="modal fade" id="deleteModal{{ $notification->id }}" tabindex="-1"
                                         role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this notification?
                                                </div>
                                                <div class="modal-footer">
                                                    <form
                                                        action="{{ route('admin.admin_notifications.destroy', $notification->id) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary"
                                                            data-dismiss="modal">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Modal -->
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
        $(document).ready(function() {
            $('#notifications-table').DataTable({
                'order': [[0, 'desc']],
                'columnDefs': [
                    { 'orderable': false, 'targets': [3, 5] }
                ]
            });
        });
    </script>
@endsection
