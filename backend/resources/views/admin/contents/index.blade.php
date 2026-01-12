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
                                <span class="badge badge-secondary">#{{ $content->id }}</span>
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
                                        <button class="btn btn-sm btn-danger" 
                                                data-toggle="modal" 
                                                data-target="#deleteModal{{ $content->id }}"
                                                title="Удалить"
                                                data-toggle-tooltip="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                        <div class="modal fade" id="deleteModal{{ $content->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content modal-modern">
                                                    <div class="modal-header modal-header-modern bg-danger text-white">
                                                        <h5 class="modal-title">Подтверждение удаления</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body modal-body-modern text-left">
                                                        Это действие навсегда удалит блок контента <strong>{{ $content->name }}</strong>.
                                                        Вы уверены?
                                                    </div>
                                                    <div class="modal-footer modal-footer-modern">
                                                        <form action="{{ route('admin.contents.destroy', $content) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-modern">Да, удалить</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">Отмена</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                "dom": '<"d-flex justify-content-between align-items-center mb-3"l<"ml-auto"f>>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            });

            // Фильтры по табу
            $('#filterAll').on('click', function() {
                table.column(3).search('').draw();
            });

            $('#filterSystem').on('click', function() {
                table.column(3).search('Системный').draw();
            });

            $('#filterCustom').on('click', function() {
                table.column(3).search('Пользовательский').draw();
            });

            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle-tooltip="tooltip"]').tooltip();
        });
    </script>
@endsection
