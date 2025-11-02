@extends('adminlte::page')

@section('title', 'Использования промокодов')

@section('content_header')
    <h1>Использования промокодов</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Список использований</h3>
                    <a href="{{ route('admin.promocodes.index') }}" class="btn btn-secondary float-right">Назад</a>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex flex-wrap align-items-center">
                        <div class="form-inline mr-3 mb-2">
                            <label for="promoFilter" class="mr-2">Промокод:</label>
                            <select id="promoFilter" class="select2 filter-select form-control form-control-sm">
                                <option value="">Все</option>
                            </select>
                        </div>
                        <div class="form-inline mr-3 mb-2">
                            <label for="userFilter" class="mr-2">Пользователь:</label>
                            <select id="userFilter" class="select2 filter-select form-control form-control-sm">
                                <option value="">Все</option>
                            </select>
                        </div>
                        <button id="resetUsageFilters" type="button" class="btn btn-sm btn-outline-secondary mb-2">Сбросить</button>
                    </div>
                    <table id="promocode-usages-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 60px">ID</th>
                            <th>Промокод</th>
                            <th>Пользователь</th>
                            <th>Заказ</th>
                            <th>Использован</th>
                            <th>Создан</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($usages as $usage)
                            <tr>
                                <td>{{ $usage->id }}</td>
                                <td data-code="{{ $usage->promocode->code ?? '' }}">
                                    @if($usage->promocode)
                                        <a href="{{ route('admin.promocodes.edit', $usage->promocode) }}">
                                            <code>{{ $usage->promocode->code }}</code>
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-user="{{ optional($usage->user)->email ?? '' }}">{{ optional($usage->user)->email ?? '—' }}</td>
                                <td>{{ $usage->order_id ?? '—' }}</td>
                                <td>{{ $usage->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $usage->created_at->format('Y-m-d H:i') }}</td>
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
    <style>
        .filter-select + .select2 .select2-selection { height: 38px !important; }
        .filter-select + .select2 .select2-selection__rendered { line-height: 35px !important; }
        .filter-select + .select2 .select2-selection__arrow { height: 38px !important; top: 0px; }
        #promoFilter.filter-select + .select2 { min-width: 300px; }
        #userFilter.filter-select + .select2 { min-width: 260px; }
        #resetUsageFilters { height: 38px; display: inline-flex; align-items: center; }
    </style>
    <script>
        $(document).ready(function () {
            var table = $('#promocode-usages-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ru.json'
                },
                order: [[0, 'desc']],
                initComplete: function() {
                    var api = this.api();
                    var promos = {};
                    var users = {};
                    api.rows().every(function() {
                        var row = this.data();
                        var promoText = $('td:eq(1)', this.node()).data('code') || '';
                        var userText = $('td:eq(2)', this.node()).data('user') || '';
                        if (promoText && promoText !== '—') promos[promoText] = true;
                        if (userText && userText !== '—') users[userText] = true;
                    });
                    var $promo = $('#promoFilter');
                    Object.keys(promos).sort().forEach(function(p) { $promo.append($('<option/>',{value:p,text:p})); });
                    var $user = $('#userFilter');
                    Object.keys(users).sort().forEach(function(u) { $user.append($('<option/>',{value:u,text:u})); });
                }
            });

            // Always show search in Select2 for better UX
            $('#promoFilter').select2({ width: 'resolve', minimumResultsForSearch: 0 });
            $('#userFilter').select2({ width: 'resolve', minimumResultsForSearch: 0 });

            // Preselect promo if provided via query (?promocode=CODE)
            var urlParams = new URLSearchParams(window.location.search);
            var prePromo = urlParams.get('promocode');
            if (prePromo) {
                // ensure option exists
                if ($('#promoFilter option[value="' + prePromo + '"]').length === 0) {
                    $('#promoFilter').append($('<option/>', { value: prePromo, text: prePromo }));
                }
                $('#promoFilter').val(prePromo).trigger('change');
            }

            // Column filters using data-* for stable matching
            $('#promoFilter').on('change', function() {
                var val = $(this).val();
                table.column(1).search(''); // clear standard search
                if (val) {
                    table.rows().every(function() {
                        var code = $('td:eq(1)', this.node()).data('code') || '';
                        $(this.node()).toggle(code === val);
                    });
                } else {
                    table.rows().every(function() { $(this.node()).show(); });
                }
                table.draw(false);
            });
            $('#userFilter').on('change', function() {
                var val = $(this).val();
                table.column(2).search('');
                if (val) {
                    table.rows().every(function() {
                        var email = $('td:eq(2)', this.node()).data('user') || '';
                        $(this.node()).toggle(email === val);
                    });
                } else {
                    table.rows().every(function() { $(this.node()).show(); });
                }
                table.draw(false);
            });

            $('#resetUsageFilters').on('click', function() {
                $('#promoFilter').val('').trigger('change');
                $('#userFilter').val('').trigger('change');
                table.search('');
                table.draw();
            });
        });
    </script>
@endsection
