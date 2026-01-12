@extends('adminlte::page')

@section('title', 'Использование промокодов')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    История использования
                </h1>
                <p class="text-muted mb-0 mt-1">Детальный лог активации промокодов пользователями</p>
            </div>
            <div>
                <a href="{{ route('admin.promocodes.index') }}" class="btn btn-secondary btn-modern">
                    <i class="fas fa-arrow-left mr-2"></i>К промокодам
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего активаций</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Уникальных пользователей</div>
                        <div class="stat-value">{{ $statistics['unique_users'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Самый популярный</div>
                        <div class="stat-value">{{ $statistics['most_used'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern p-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small font-weight-bold text-muted uppercase">Промокод</label>
                    <select id="promoFilter" class="select2 form-control form-control-sm">
                        <option value="">Все промокоды</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small font-weight-bold text-muted uppercase">Пользователь</label>
                    <select id="userFilter" class="select2 form-control form-control-sm">
                        <option value="">Все пользователи</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="resetUsageFilters" type="button" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-undo mr-1"></i>Сбросить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="promocode-usages-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Промокод</th>
                        <th>Пользователь</th>
                        <th class="text-center">Заказ</th>
                        <th class="text-center">Дата активации</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($usages as $usage)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $usage->id }}</span>
                            </td>
                            <td class="align-middle" data-code="{{ $usage->promocode->code ?? '' }}">
                                @if($usage->promocode)
                                    <a href="{{ route('admin.promocodes.edit', $usage->promocode) }}" class="font-weight-bold text-primary">
                                        <code>{{ $usage->promocode->code }}</code>
                                    </a>
                                @else
                                    <span class="text-muted italic small">Удален</span>
                                @endif
                            </td>
                            <td class="align-middle" data-user="{{ optional($usage->user)->email ?? '' }}">
                                @if($usage->user)
                                    <div class="font-weight-bold text-dark">{{ $usage->user->name }}</div>
                                    <div class="text-muted small">{{ $usage->user->email }}</div>
                                @else
                                    <span class="text-muted small italic">Гость</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($usage->order_id)
                                    <span class="badge badge-light border">{{ $usage->order_id }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-muted">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ $usage->created_at->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ $usage->created_at->format('H:i') }}
                                </small>
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
        .select2-container--default .select2-selection--single {
            height: 31px !important;
            padding: 2px 5px !important;
            font-size: 0.875rem !important;
            border-color: #e3e6f0 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 29px !important;
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            var table = $('#promocode-usages-table').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "order": [[0, "desc"]],
                "pageLength": 25,
                "dom": '<"d-flex justify-content-between align-items-center mb-3"l<"ml-auto"f>>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                initComplete: function() {
                    var api = this.api();
                    var promos = {};
                    var users = {};
                    api.rows().every(function() {
                        var promoText = $('td:eq(1)', this.node()).data('code') || '';
                        var userText = $('td:eq(2)', this.node()).data('user') || '';
                        if (promoText && promoText !== '') promos[promoText] = true;
                        if (userText && userText !== '') users[userText] = true;
                    });
                    var $promo = $('#promoFilter');
                    Object.keys(promos).sort().forEach(function(p) { $promo.append($('<option/>',{value:p,text:p})); });
                    var $user = $('#userFilter');
                    Object.keys(users).sort().forEach(function(u) { $user.append($('<option/>',{value:u,text:u})); });
                }
            });

            $('#promoFilter, #userFilter').select2({ width: '100%' });

            // Фильтры
            $('#promoFilter').on('change', function() {
                var val = $(this).val();
                table.column(1).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            $('#userFilter').on('change', function() {
                var val = $(this).val();
                table.column(2).search(val ? val : '', true, false).draw();
            });

            $('#resetUsageFilters').on('click', function() {
                $('#promoFilter').val('').trigger('change');
                $('#userFilter').val('').trigger('change');
                table.search('').columns().search('').draw();
            });
        });
    </script>
@endsection
