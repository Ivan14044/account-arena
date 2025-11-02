@extends('adminlte::page')

@section('title', 'Email templates')

@section('content_header')
    <h1>Email templates</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Templates list</h3>
                </div>
                <div class="card-body">
                    <table id="email-templates-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th>Name</th>
                            <th style="width: 110px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($emailTemplates as $emailTemplate)
                            <tr>
                                <td>{{ $emailTemplate->id }}</td>
                                <td>{{ $emailTemplate->name }}</td>
                                <td>
                                    <a href="{{ route('admin.email-templates.show', $emailTemplate) }}"
                                       target="_blank" class="btn btn-sm btn-success">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
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
            $('#email-templates-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 2 }
                ]
            });
        });
    </script>
@endsection
