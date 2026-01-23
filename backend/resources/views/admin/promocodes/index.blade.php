@extends('adminlte::page')

@section('title', 'Промокоды')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление промокодами
                </h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Создание и настройка промокодов для скидок и бесплатного доступа</p>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                <a href="{{ route('admin.promocode-usages.index') }}" class="btn btn-outline-primary btn-modern w-100 w-md-auto">
                    <i class="fas fa-clipboard-list mr-2"></i>История использования
                </a>
                <a href="{{ route('admin.promocodes.create') }}" class="btn btn-primary btn-modern w-100 w-md-auto">
                    <i class="fas fa-plus mr-2"></i>Создать промокод
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
        <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего промокодов</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $statistics['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Использований</div>
                        <div class="stat-value">{{ $statistics['usages'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
            <div class="stat-card stat-card-warning stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ср. скидка</div>
                        <div class="stat-value">{{ $statistics['avg_discount'] }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 font-weight-normal">Список промокодов</h5>
                    <small class="text-muted">Всего записей: {{ $statistics['total'] }}</small>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <!-- Фильтры -->
            <div class="p-3 border-bottom bg-light">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label small font-weight-bold">Партия:</label>
                            <select id="batch" class="select2 filter-select form-control form-control-sm">
                                <option value="">Все партии</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label small font-weight-bold">Статус:</label>
                            <select id="statusFilter" class="select2 filter-select form-control form-control-sm">
                                <option value="">Все статусы</option>
                                <option value="Активен">Активен</option>
                                <option value="Приостановлен">Приостановлен</option>
                                <option value="Запланирован">Запланирован</option>
                                <option value="Истек">Истек</option>
                                <option value="Исчерпан">Исчерпан</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label small font-weight-bold">Тип:</label>
                            <select id="typeFilter" class="select2 filter-select form-control form-control-sm">
                                <option value="">Все типы</option>
                                <option value="Скидка">Скидка</option>
                                <option value="Бесплатный доступ">Бесплатный доступ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button id="resetFilters" type="button" class="btn btn-secondary btn-sm w-100 mb-2">
                            <i class="fas fa-redo mr-2"></i>Сбросить фильтры
                        </button>
                    </div>
                </div>
            </div>

            <!-- Массовые действия -->
            <div class="p-3 border-bottom d-flex align-items-center">
                <button id="bulkDelete" class="btn btn-sm btn-danger btn-modern" disabled>
                    <i class="fas fa-trash-alt mr-2"></i>Удалить выбранные
                </button>
                <small class="text-muted ml-3">Выберите промокоды для массового удаления</small>
            </div>

            <div class="table-responsive">
                <table id="promocodes-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 40px" class="text-center">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th style="width: 50px" class="text-center">ID</th>
                            <th style="min-width: 150px">Код</th>
                            <th>Партия</th>
                            <th>Тип</th>
                            <th class="text-center">Скидка</th>
                            <th class="text-center">Использований</th>
                            <th class="text-center">Статус</th>
                            <th style="width: 140px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promocodes as $promocode)
                        @php
                            $now = now();
                            $paused = !$promocode->is_active;
                            $expired = $promocode->expires_at && $promocode->expires_at->lt($now);
                            $scheduled = $promocode->starts_at && $promocode->starts_at->gt($now);
                            $exhausted = ($promocode->usage_limit ?? 0) > 0 && ($promocode->usage_count ?? 0) >= $promocode->usage_limit;
                        @endphp
                        <tr>
                            <td class="text-center align-middle">
                                <input type="checkbox" class="row-select" value="{{ $promocode->id }}">
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $promocode->id }}</span>
                            </td>
                            <td class="align-middle">
                                <code style="font-size: 0.95rem; background: #f8f9fc; padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e3e6f0; font-weight: 600;">
                                    {{ $promocode->code }}
                                </code>
                                @if($promocode->prefix)
                                    <span class="badge badge-info badge-modern ml-1" style="font-size: 0.65rem;">{{ $promocode->prefix }}</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($promocode->batch_id)
                                    <span class="badge badge-light font-weight-bold text-muted">{{ $promocode->batch_id }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($promocode->type === 'free_access')
                                    <span class="badge badge-info badge-modern">
                                        <i class="fas fa-gift mr-1"></i>Бесплатный доступ
                                    </span>
                                @else
                                    <span class="badge badge-primary badge-modern">
                                        <i class="fas fa-percentage mr-1"></i>Скидка
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <span class="font-weight-bold text-primary">
                                    {{ $promocode->percent_discount }}%
                                </span>
                            </td>
                            <td class="text-center align-middle">
                                @if($promocode->usage_limit === 0)
                                    <span class="text-muted small">{{ $promocode->usage_count }} / ∞</span>
                                @else
                                    <span class="badge badge-{{ $exhausted ? 'warning' : 'light' }} badge-modern">
                                        {{ $promocode->usage_count }} / {{ $promocode->usage_limit }}
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($paused)
                                    <span class="badge badge-secondary badge-modern">
                                        <i class="fas fa-pause mr-1"></i>Приостановлен
                                    </span>
                                @elseif($expired)
                                    <span class="badge badge-danger badge-modern">
                                        <i class="fas fa-clock mr-1"></i>Истек
                                    </span>
                                @elseif($exhausted)
                                    <span class="badge badge-warning badge-modern">
                                        <i class="fas fa-times-circle mr-1"></i>Исчерпан
                                    </span>
                                @elseif($scheduled)
                                    <span class="badge badge-info badge-modern">
                                        <i class="fas fa-calendar-alt mr-1"></i>Запланирован
                                    </span>
                                @else
                                    <span class="badge badge-success badge-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Активен
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.promocodes.edit', $promocode) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.promocode-usages.index', ['promocode' => $promocode->code]) }}"
                                       class="btn btn-sm {{ ($promocode->usage_count ?? 0) > 0 ? 'btn-info' : 'btn-secondary' }}"
                                       title="История использования"
                                       data-toggle="tooltip">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-delete-promocode" 
                                            data-code="{{ $promocode->code }}"
                                            data-usage="{{ $promocode->usage_count }}"
                                            data-action="{{ route('admin.promocodes.destroy', $promocode) }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($promocodes->hasPages())
                <div class="px-4 py-3 border-top">
                    <div class="d-flex justify-content-center">
                        {{ $promocodes->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
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
                    <i class="fas fa-tags fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold">Промокод <code id="delete-promocode-code"></code></h6>
                    <p class="text-muted mb-0">Использований: <span id="delete-promocode-usage"></span></p>
                    <p class="mt-3">Вы действительно хотите удалить этот промокод?<br>
                    <small class="text-danger">Это действие нельзя отменить!</small></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-promocode-form" method="POST">
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
    <style>
        .filter-select + .select2 .select2-selection {
            height: 31px !important;
            border: 1px solid #d1d3e2 !important;
            border-radius: 0.25rem !important;
        }
        .filter-select + .select2 .select2-selection__rendered {
            line-height: 28px !important;
            font-size: 0.8rem;
        }
        .filter-select + .select2 .select2-selection__arrow {
            height: 28px !important;
        }
        .badge-batch {
            font-size: 10px;
            margin-left: 5px;
        }
    </style>
@endsection

@section('js')
    <script>
        function formatBatch(option) {
            if (!option.id) return option.text;
            var active = $(option.element).data('active');
            if (active && active > 0) {
                return $('<span>' + option.text + ' <span class="badge badge-success badge-batch">' + active + '</span></span>');
            }
            return option.text;
        }

        $(document).ready(function() {
            var table = $('#promocodes-table').DataTable({
                "order": [[1, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "paging": false,
                "info": false,
                "columnDefs": [
                    { "orderable": false, "targets": [0, 8] }
                ],
                initComplete: function() {
                    var api = this.api();
                    var batches = {};
                    api.rows().every(function() {
                        var row = this.data();
                        var batchHtml = row[3];
                        var statusHtml = row[7];
                        var batchText = $('<div>').html(batchHtml).text().trim();
                        var statusText = $('<div>').html(statusHtml).text();
                        var statusClean = statusText.replace(/\s+/g, ' ').trim();
                        var isActiveOrScheduled = /\bАктивен\b/i.test(statusClean) || /\bЗапланирован\b/i.test(statusClean);
                        if (batchText && batchText !== '—' && isActiveOrScheduled) {
                            batches[batchText] = (batches[batchText] || 0) + 1;
                        }
                    });
                    var $batch = $('#batch');
                    Object.keys(batches).sort().forEach(function(b) {
                        var count = batches[b];
                        var $opt = $('<option/>', { value: b, text: b }).attr('data-active', count);
                        $batch.append($opt);
                    });
                }
            });

            // Фильтры
            $('#batch').select2({
                placeholder: 'Все партии',
                allowClear: true,
                templateResult: formatBatch,
                templateSelection: formatBatch,
                minimumResultsForSearch: -1
            }).on('change', function() {
                var val = $(this).val();
                table.column(3).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
            });

            $('#statusFilter').select2({ minimumResultsForSearch: -1 }).on('change', function() {
                var val = $(this).val();
                table.column(7).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            $('#typeFilter').select2({ minimumResultsForSearch: -1 }).on('change', function() {
                var val = $(this).val();
                table.column(4).search(val ? val : '', false, false).draw();
            });

            $('#resetFilters').on('click', function() {
                $('#batch, #statusFilter, #typeFilter').val('').trigger('change');
                table.search('').columns().search('').draw();
            });

            // Массовое удаление
            $('#selectAll').on('change', function() {
                $('.row-select').prop('checked', this.checked);
                updateBulkBtn();
            });

            $('#promocodes-table').on('change', '.row-select', function() {
                updateBulkBtn();
            });

            function updateBulkBtn() {
                var count = $('.row-select:checked').length;
                $('#bulkDelete').prop('disabled', count === 0).html('<i class="fas fa-trash-alt mr-2"></i>Удалить выбранные (' + count + ')');
            }

            $('#bulkDelete').on('click', function() {
                var ids = $('.row-select:checked').map(function() { return $(this).val(); }).get();
                if (confirm('Вы уверены, что хотите удалить выбранные промокоды (' + ids.length + ' шт.)?')) {
                    $.ajax({
                        url: '{{ route('admin.promocodes.bulk-destroy') }}',
                        method: 'DELETE',
                        data: { ids: ids, _token: '{{ csrf_token() }}' },
                        success: function() { location.reload(); }
                    });
                }
            });

            // ДИНАМИЧЕСКИЕ МОДАЛКИ
            $('.btn-delete-promocode').on('click', function() {
                $('#delete-promocode-code').text($(this).data('code'));
                $('#delete-promocode-usage').text($(this).data('usage'));
                $('#delete-promocode-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });

            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
