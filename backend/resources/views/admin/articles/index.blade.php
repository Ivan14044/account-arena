@extends('adminlte::page')

@section('title', 'Статьи')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Статьи (Блог)
                </h1>
                <p class="text-muted mb-0 mt-1">Управление контентом блога и новостями платформы</p>
            </div>
            <div>
                <a href="{{ route('admin.articles.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить статью
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4 articles-stats">
        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего статей</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Опубликовано</div>
                        <div class="stat-value">{{ $statistics['published'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-warning stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Черновики</div>
                        <div class="stat-value">{{ $statistics['draft'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab" id="filterAll">Все</a></li>
                <li class="nav-item"><a class="nav-link" href="#published" data-toggle="tab" id="filterPublished">Опубликовано</a></li>
                <li class="nav-item"><a class="nav-link" href="#draft" data-toggle="tab" id="filterDraft">Черновики</a></li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="articles-table" class="table table-hover modern-table articles-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th style="width: 80px" class="text-center">Превью</th>
                        <th>Название</th>
                        <th>Категории</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Дата создания</th>
                        <th style="width: 120px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $article->id }}</span>
                            </td>
                            <td class="text-center align-middle">
                                @if($article->img)
                                    @php($imgSrc = \Illuminate\Support\Str::startsWith($article->img, ['http://', 'https://', '/storage/']) ? $article->img : asset('img/articles/' . $article->img))
                                    <img src="{{ $imgSrc }}" alt="" class="rounded shadow-sm" width="50" height="50" style="object-fit: cover; border: 1px solid #e3e6f0;">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: #f8f9fc; border: 1px solid #e3e6f0;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="align-middle font-weight-bold">
                                {{ $article->admin_name }}
                            </td>
                            <td class="align-middle">
                                @foreach ($article->categories as $category)
                                    <span class="badge badge-info badge-modern mb-1">{{ $category->admin_name ?? '-' }}</span>
                                @endforeach
                            </td>
                            <td class="text-center align-middle">
                                @if ($article->status === 'published')
                                    <span class="badge badge-success badge-modern">Опубликовано</span>
                                @else
                                    <span class="badge badge-warning badge-modern">Черновик</span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-muted" data-order="{{ strtotime($article->created_at) }}">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($article->created_at)->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($article->created_at)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.articles.edit', $article) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-danger btn-delete-article" 
                                            data-name="{{ $article->admin_name }}"
                                            data-action="{{ route('admin.articles.destroy', $article->id) }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                <div class="modal-body py-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-newspaper fa-3x text-danger mb-3"></i>
                        <h6 class="font-weight-bold" id="delete-article-name"></h6>
                    </div>
                    <p class="text-center mb-0">
                        Вы действительно хотите удалить эту статью?<br>
                        <small class="text-danger">Это действие нельзя отменить!</small>
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-article-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Отмена
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
        $(document).ready(function() {
            var table = $('#articles-table').DataTable({
                'order': [[0, 'desc']],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                'columnDefs': [
                    { 'orderable': false, 'targets': [1, 6] }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            // Фильтры по табу
            $('#filterAll').on('click', function() {
                table.column(4).search('').draw();
            });

            $('#filterPublished').on('click', function() {
                table.column(4).search('Опубликовано').draw();
            });

            $('#filterDraft').on('click', function() {
                table.column(4).search('Черновик').draw();
            });

            $('[data-toggle="tooltip"]').tooltip();

            // ДИНАМИЧЕСКИЕ МОДАЛКИ
            $('.btn-delete-article').on('click', function() {
                const name = $(this).data('name');
                const action = $(this).data('action');

                $('#delete-article-name').text(name);
                $('#delete-article-form').attr('action', action);
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
