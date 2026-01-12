@extends('adminlte::page')

@section('title', 'Email шаблоны')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Email шаблоны
                </h1>
                <p class="text-muted mb-0 mt-1">Управление шаблонами писем для автоматических рассылок</p>
            </div>
            <div>
                <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Создать шаблон
                </a>
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

    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="email-templates-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Название шаблона</th>
                        <th style="width: 150px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($emailTemplates as $emailTemplate)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $emailTemplate->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold text-dark">
                                {{ $emailTemplate->name }}
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.email-templates.show', $emailTemplate) }}"
                                       target="_blank" class="btn btn-sm btn-info"
                                       title="Предпросмотр"
                                       data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" 
                                       class="btn btn-sm btn-primary"
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#email-templates-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 2 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
