@extends('adminlte::page')

@section('title', 'Proxies')

@section('content_header')
    <h1>Proxies</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Proxies list</h3>
                    <a href="{{ route('admin.proxies.create') }}" class="btn btn-primary float-right">+ Add</a>
                </div>
                <div class="card-body">
                    <table id="proxies-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th>Full proxy</th>
                            <th>Status</th>
                            <th>Country</th>
                            <th>Created at</th>
                            <th>Expiring at</th>
                            <th style="width: 70px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($proxies as $proxy)
                            <tr>
                                <td>{{ $proxy->id }}</td>
                                <td>{{ $proxy->getFullProxy() }}</td>
                                <td>
                                    @if(!$proxy->is_active)
                                        <span class="badge badge-danger">Inactive</span>
                                    @else
                                        <span class="badge badge-success">Active</span>
                                    @endif
                                </td>
                                <td>{{ strtoupper($proxy->country) }}</td>
                                <td data-order="{{ strtotime($proxy->created_at) }}">
                                    {{ \Carbon\Carbon::parse($proxy->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td data-order="{{ $proxy->expiring_at ? strtotime($proxy->expiring_at) : 0 }}">
                                    {{ $proxy->expiring_at ? \Carbon\Carbon::parse($proxy->expiring_at)->format('Y-m-d H:i') : null }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.proxies.edit', $proxy) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $proxy->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <div class="modal fade" id="deleteModal{{ $proxy->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this proxy?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('admin.proxies.destroy', $proxy) }}" method="POST" class="d-inline">
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
            $('#proxies-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 6 }
                ]
            });
        });
    </script>
@endsection
