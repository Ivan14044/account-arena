@extends('adminlte::page')

@section('title', 'Services')

@section('content_header')
    <h1>Services</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Services list</h3>
                    <a href="{{ route('admin.services.create') }}" class="btn btn-primary float-right">+ Add</a>
                </div>
                <div class="card-body">
                    <table id="services-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">Position</th>
                            <th style="width: 40px">ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created at</th>
                            <th style="width: 90px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td>{{ $service->position }}</td>
                                <td>{{ $service->id }}</td>
                                <td>
                                    @if($service->logo)
                                        <img src="{{ asset($service->logo) }}"
                                             alt="Logo"
                                             class="mr-1 rounded"
                                             width="36" height="36">
                                    @endif
                                    {{ $service->admin_name }}
                                </td>
                                <td>
                                    @if(!$service->is_active)
                                        <span class="badge badge-danger">Inactive</span>
                                    @else
                                        <span class="badge badge-success">Active</span>
                                    @endif
                                </td>
                                <td data-order="{{ strtotime($service->created_at) }}">
                                    {{ \Carbon\Carbon::parse($service->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $service->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <div class="modal fade" id="deleteModal{{ $service->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this service?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
            $('#services-table').DataTable({
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 5 }
                ]
            });
        });
    </script>
@endsection
