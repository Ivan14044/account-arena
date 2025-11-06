@extends('adminlte::page')

@section('title', 'Промокоды')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление промокодами
                </h1>
                <p class="text-muted mb-0 mt-1">Создание и настройка промокодов для скидок и бесплатного доступа</p>
            </div>
            <div class="d-flex" style="gap: 0.75rem;">
                <a href="{{ route('admin.promocode-usages.index') }}" class="btn btn-outline-primary btn-modern">
                    <i class="fas fa-clipboard-list mr-2"></i>История использования
                </a>
                <a href="{{ route('admin.promocodes.create') }}" class="btn btn-primary btn-modern">
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
        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего промокодов</div>
                        <div class="stat-value">{{ $promocodes->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $promocodes->where('is_active', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Использований</div>
                        <div class="stat-value">{{ $promocodes->sum('usage_count') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ср. скидка</div>
                        <div class="stat-value">{{ $promocodes->count() > 0 ? round($promocodes->avg('percent_discount'), 1) : 0 }}%</div>
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
                    <h5 class="mb-0">Список промокодов</h5>
                    <small class="text-muted">Всего записей: {{ $promocodes->count() }}</small>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <!-- Фильтры -->
            <div class="mb-4 bg-modern p-3" style="border-radius: 0.5rem;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group-modern mb-2">
                            <label for="batch" class="form-label-modern">Партия:</label>
                            <select id="batch" class="select2 filter-select form-control form-control-modern">
                                <option value="">Все партии</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group-modern mb-2">
                            <label for="statusFilter" class="form-label-modern">Статус:</label>
                            <select id="statusFilter" class="select2 filter-select form-control form-control-modern">
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
                        <div class="form-group-modern mb-2">
                            <label for="typeFilter" class="form-label-modern">Тип:</label>
                            <select id="typeFilter" class="select2 filter-select form-control form-control-modern">
                                <option value="">Все типы</option>
                                <option value="Скидка">Скидка</option>
                                <option value="Бесплатный доступ">Бесплатный доступ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button id="resetFilters" type="button" class="btn btn-outline-secondary btn-modern w-100 mb-2">
                            <i class="fas fa-redo mr-2"></i>Сбросить фильтры
                        </button>
                    </div>
                </div>
            </div>

            <!-- Массовые действия -->
            <div class="mb-3">
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
                            <th>Префикс</th>
                            <th>Партия</th>
                            <th>Тип</th>
                            <th class="text-center">Скидка</th>
                            <th class="text-center">Использований</th>
                            <th class="text-center">Начало</th>
                            <th class="text-center">Окончание</th>
                            <th>Статус</th>
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
                                <span class="badge badge-secondary">#{{ $promocode->id }}</span>
                            </td>
                            <td class="align-middle">
                                <code style="font-size: 0.95rem; background: #f8f9fc; padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e3e6f0; font-weight: 600;">
                                    {{ $promocode->code }}
                                </code>
                            </td>
                            <td class="align-middle">
                                @if($promocode->prefix)
                                    <span class="badge badge-info badge-modern">{{ $promocode->prefix }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($promocode->batch_id)
                                    <span class="badge badge-light font-weight-bold">{{ $promocode->batch_id }}</span>
                                @else
                                    <span class="text-muted">—</span>
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
                                <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">
                                    {{ $promocode->percent_discount }}%
                                </span>
                            </td>
                            <td class="text-center align-middle">
                                @if($promocode->usage_limit === 0)
                                    <span class="badge badge-light">{{ $promocode->usage_count }} / ∞</span>
                                @else
                                    <span class="badge badge-{{ $exhausted ? 'warning' : 'light' }}">
                                        {{ $promocode->usage_count }} / {{ $promocode->usage_limit }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($promocode->starts_at)
                                    <small class="text-muted">{{ $promocode->starts_at->format('d.m.Y') }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($promocode->expires_at)
                                    <small class="text-muted">{{ $promocode->expires_at->format('d.m.Y') }}</small>
                                @else
                                    <span class="text-muted">—</span>
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
                                       data-toggle="tooltip"
                                       @if(($promocode->usage_count ?? 0) === 0) disabled @endif>
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $promocode->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $promocode->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header-modern">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                                                    Подтверждение удаления
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body-modern text-center">
                                                <i class="fas fa-tags fa-3x text-danger mb-3"></i>
                                                <h6 class="font-weight-bold">Промокод <code>{{ $promocode->code }}</code></h6>
                                                <p class="text-muted mb-0">Использований: {{ $promocode->usage_count }}</p>
                                                <small class="text-danger">Это действие нельзя отменить!</small>
                                            </div>
                                            <div class="modal-footer-modern justify-content-center">
                                                <form action="{{ route('admin.promocodes.destroy', $promocode) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-modern">
                                                        <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">
                                                    Отмена
                                                </button>
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
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .filter-select + .select2 .select2-selection {
            height: 38px !important;
            border: 1px solid #d1d3e2 !important;
            border-radius: 0.375rem !important;
        }

        .filter-select + .select2 .select2-selection__rendered {
            line-height: 35px !important;
            font-size: 0.875rem;
        }

        .filter-select + .select2 .select2-selection__arrow {
            height: 38px !important;
        }

        .badge-batch {
            font-size: 11px;
            margin-left: 5px;
            display: inline;
        }
    </style>
@endsection

@section('js')
    <script>
        function formatBatch(option) {
            if (!option.id) return option.text;
            var active = $(option.element).data('active');
            if (active && active > 0) {
                return $(
                    '<span>' + option.text + ' <span class="badge badge-success badge-batch">' + active + '</span></span>'
                );
            }
            return option.text;
        }

        $(document).ready(function() {
            // DataTable
            var table = $('#promocodes-table').DataTable({
                "order": [[1, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": [0, 11] }
                ],
                initComplete: function() {
                    var api = this.api();
                    var batches = {};
                    api.rows().every(function() {
                        var row = this.data();
                        var batchHtml = row[4];
                        var statusHtml = row[10];
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

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры
            var $batch = $('#batch');
            $batch.select2({
                placeholder: 'Все партии',
                allowClear: true,
                templateResult: formatBatch,
                templateSelection: formatBatch
            });

            $batch.on('change', function() {
                var val = $(this).val();
                table.column(4).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
            });

            $('#statusFilter').select2({ minimumResultsForSearch: -1 });
            $('#statusFilter').on('change', function() {
                var val = $(this).val();
                table.column(10).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            $('#typeFilter').select2({ minimumResultsForSearch: -1 });
            $('#typeFilter').on('change', function() {
                var val = $(this).val();
                table.column(5).search(val ? val : '', false, false).draw();
            });

            $('#resetFilters').on('click', function() {
                $('#batch').val('').trigger('change');
                $('#statusFilter').val('').trigger('change');
                $('#typeFilter').val('').trigger('change');
                table.column(4).search('');
                table.column(5).search('');
                table.column(10).search('');
                table.search('');
                table.draw();
            });

            // Массовое выделение
            function selectedIds() {
                var ids = [];
                table.rows({ search: 'applied' }).every(function() {
                    var $node = $(this.node());
                    var $checkbox = $node.find('input.row-select');
                    if ($checkbox.prop('checked')) {
                        ids.push(parseInt($checkbox.val(), 10));
                    }
                });
                return ids;
            }

            $('#selectAll').on('change', function() {
                var checked = this.checked;
                $('#promocodes-table tbody input.row-select').prop('checked', checked);
                $('#bulkDelete').prop('disabled', selectedIds().length === 0);
            });

            $('#promocodes-table').on('change', 'input.row-select', function() {
                $('#bulkDelete').prop('disabled', selectedIds().length === 0);
            });

            table.on('draw', function() {
                if ($('#selectAll').prop('checked')) {
                    $('#promocodes-table tbody input.row-select').prop('checked', true);
                }
                $('#bulkDelete').prop('disabled', selectedIds().length === 0);
            });

            $('#bulkDelete').on('click', function() {
                var ids = selectedIds();
                if (!ids.length) return;
                if (!confirm('Вы уверены, что хотите удалить выбранные промокоды (' + ids.length + ' шт.)?')) return;
                $.ajax({
                    url: '{{ route('admin.promocodes.bulk-destroy') }}',
                    method: 'DELETE',
                    data: { ids: ids, _token: '{{ csrf_token() }}' },
                    success: function() {
                        location.reload();
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Ошибка при удалении');
                    }
                });
            });

            // Автоскрытие алертов
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endsection
