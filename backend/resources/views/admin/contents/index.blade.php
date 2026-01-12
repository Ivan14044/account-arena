@extends('adminlte::page')

@section('title', 'Контент')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление контентом
                </h1>
                <p class="text-muted mb-0 mt-1">Настройка текстовых блоков и динамических элементов сайта</p>
            </div>
            <div>
                <a href="{{ route('admin.contents.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить блок
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

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего блоков</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Системные</div>
                        <div class="stat-value">{{ $statistics['system'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Пользовательские</div>
                        <div class="stat-value">{{ $statistics['custom'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab" id="filterAll">Все</a></li>
                <li class="nav-item"><a class="nav-link" href="#system" data-toggle="tab" id="filterSystem">Системные</a></li>
                <li class="nav-item"><a class="nav-link" href="#custom" data-toggle="tab" id="filterCustom">Пользовательские</a></li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="contents-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Название блока</th>
                        <th class="text-center">Код (Slug)</th>
                        <th class="text-center">Тип</th>
                        <th style="width: 120px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($contents as $content)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $content->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold">
                                {{ $content->name }}
                            </td>
                            <td class="text-center align-middle">
                                <code class="text-primary small bg-light px-2 py-1 rounded border">
                                    {{ $content->code }}
                                </code>
                            </td>
                            <td class="text-center align-middle">
                                @if($content->is_system)
                                    <span class="badge badge-info badge-modern"><i class="fas fa-lock mr-1 small"></i>Системный</span>
                                @else
                                    <span class="badge badge-secondary badge-modern"><i class="fas fa-user mr-1 small"></i>Пользовательский</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.contents.edit', $content) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if(!$content->is_system)
                                        <button class="btn btn-sm btn-danger btn-delete-content" 
                                                data-name="{{ $content->name }}"
                                                data-action="{{ route('admin.contents.destroy', $content) }}"
                                                title="Удалить"
                                                data-toggle-tooltip="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Единое модальное окно для удаления --}}
    <div class="modal fade" id="singleDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Подтверждение удаления
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-4 text-center">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold" id="delete-content-name"></h6>
                    <p class="mt-3">Вы действительно хотите удалить этот блок контента?<br>
                    <small class="text-danger">Это действие нельзя отменить!</small></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-content-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-modern">
                            <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Отмена
                    </button>
                </div>
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
            var table = $('#contents-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 4 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            // Фильтры по табу
            $('#filterAll').on('click', function() { table.column(3).search('').draw(); });
            $('#filterSystem').on('click', function() { table.column(3).search('Системный').draw(); });
            $('#filterCustom').on('click', function() { table.column(3).search('Пользовательский').draw(); });

            $('[data-toggle="tooltip"]').tooltip();

            // Динамическая модалка
            $('.btn-delete-content').on('click', function() {
                $('#delete-content-name').text($(this).data('name'));
                $('#delete-content-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
