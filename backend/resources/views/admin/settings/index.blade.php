@extends('adminlte::page')

@section('title', 'Settings')

@section('content_header')
    <h1>Settings</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header no-border border-0 p-0">
                    <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active"
                               id="tab_subscriptions" data-toggle="pill" href="#content_subscriptions" role="tab">
                                Subscriptions settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                               id="tab_header_menu" data-toggle="pill" href="#content_header_menu" role="tab">
                                Header menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                               id="tab_footer_menu" data-toggle="pill" href="#content_footer_menu" role="tab">
                                Footer menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                               id="tab_smtp" data-toggle="pill" href="#content_smtp" role="tab">
                                SMTP
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link"
                               id="tab_cookie" data-toggle="pill" href="#content_cookie" role="tab">
                                Cookie
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="content_subscriptions" role="tabpanel">
                            <form method="POST" action="{{ route('admin.settings.store') }}">
                                <input type="hidden" name="form" value="subscriptions">
                                @csrf
                                <div class="form-group">
                                    <label for="trial_days">Trial days</label>
                                    <input type="text" name="trial_days" id="trial_days" class="form-control @error('trial_days') is-invalid @enderror"
                                           value="{{ old('trial_days', \App\Models\Option::get('trial_days')) }}">
                                    @error('trial_days')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="currency">Currency</label>
                                    <select name="currency" id="currency" class="form-control @error('currency') is-invalid @enderror">
                                        <option value="usd" {{ old('currency', $currency) == 'usd' ? 'selected' : '' }}>USD</option>
                                        <option value="eur" {{ old('currency', $currency) == 'eur' ? 'selected' : '' }}>EUR</option>
                                        <option value="uah" {{ old('currency', $currency) == 'uah' ? 'selected' : '' }}>UAH</option>
                                        <option value="rub" {{ old('currency', $currency) == 'rub' ? 'selected' : '' }}>RUB</option>
                                        <option value="byn" {{ old('currency', $currency) == 'byn' ? 'selected' : '' }}>BYN</option>
                                        <option value="kzt" {{ old('currency', $currency) == 'kzt' ? 'selected' : '' }}>KZT</option>
                                        <option value="gel" {{ old('currency', $currency) == 'gel' ? 'selected' : '' }}>GEL</option>
                                        <option value="mdl" {{ old('currency', $currency) == 'mdl' ? 'selected' : '' }}>MDL</option>
                                        <option value="pln" {{ old('currency', $currency) == 'pln' ? 'selected' : '' }}>PLN</option>
                                        <option value="chf" {{ old('currency', $currency) == 'chf' ? 'selected' : '' }}>CHF</option>
                                        <option value="sek" {{ old('currency', $currency) == 'sek' ? 'selected' : '' }}>SEK</option>
                                        <option value="czk" {{ old('currency', $currency) == 'czk' ? 'selected' : '' }}>CZK</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="discount_2">Discount for 2 services (%)</label>
                                    <input type="number" step="1" min="0" max="99" name="discount_2" id="discount_2" class="form-control @error('discount_2') is-invalid @enderror"
                                           value="{{ old('discount_2', \App\Models\Option::get('discount_2')) }}">
                                    @error('discount_2')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="discount_3">Discount for 3 services (%)</label>
                                    <input type="number" step="1" min="0" max="99" name="discount_3" id="discount_3" class="form-control @error('discount_3') is-invalid @enderror"
                                           value="{{ old('discount_3', \App\Models\Option::get('discount_3')) }}">
                                    @error('discount_3')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                        </div>
                        <div class="tab-pane" id="content_header_menu" role="tabpanel">
                            <div class="card">
                                <div class="card-header no-border border-0 p-0">
                                    <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                        @foreach(config('langs') as $code => $flag)
                                            <li class="nav-item">
                                                <a class="nav-link {{ $code == 'ru' ? 'active' : null }}"
                                                   id="tab_{{ $code }}" data-toggle="pill" href="#tab_content_{{ $code }}" role="tab">
                                                    <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <form class="save-menu-form" method="POST" action="{{ route('admin.settings.store') }}">
                                        <input type="hidden" name="form" value="header_menu">
                                        @csrf
                                        <div class="tab-content">
                                            @foreach(config('langs') as $code => $flag)
                                                <input type="hidden" name="header_menu[{{ $code }}]" value="">
                                                <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="tab_content_{{ $code }}" role="tabpanel">
                                                    <div class="mb-4">
                                                        <div class="row g-1 align-items-center">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="title" placeholder="Title">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="link" placeholder="Link">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="isBlank{{ $code }}" name="is_blank">
                                                                    <label class="form-check-label" for="isBlank{{ $code }}">_blank</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" data-lang="{{ $code }}"
                                                                        data-type="header"
                                                                        class="btn btn-primary w-100 add-item"><i class="fas fa-plus"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <ul class="list-group mb-3 menu-list" data-type="header" data-lang="{{ $code }}"></ul>
                                                </div>
                                            @endforeach
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="content_footer_menu" role="tabpanel">
                            <div class="card">
                                <div class="card-header no-border border-0 p-0">
                                    <ul class="nav nav-tabs" id="custom-tabs-one-tab-footer" role="tablist">
                                        @foreach(config('langs') as $code => $flag)
                                            <li class="nav-item">
                                                <a class="nav-link {{ $code == 'ru' ? 'active' : null }}"
                                                   id="tab_{{ $code }}_footer" data-toggle="pill" href="#tab_content_{{ $code }}_footer" role="tab">
                                                    <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <form class="save-menu-form" method="POST" action="{{ route('admin.settings.store') }}">
                                        <input type="hidden" name="form" value="footer_menu">
                                        @csrf
                                        <div class="tab-content">
                                            @foreach(config('langs') as $code => $flag)
                                                <input type="hidden" name="footer_menu[{{ $code }}]" value="">
                                                <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="tab_content_{{ $code }}_footer" role="tabpanel">
                                                    <div class="mb-4">
                                                        <div class="row g-1 align-items-center">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="title" placeholder="Title">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="link" placeholder="Link">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="isBlank{{ $code }}Footer" name="is_blank">
                                                                    <label class="form-check-label" for="isBlank{{ $code }}Footer">_blank</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" data-lang="{{ $code }}"
                                                                        data-type="footer"
                                                                        class="btn btn-primary w-100 add-item"><i class="fas fa-plus"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <ul class="list-group mb-3 menu-list" data-type="footer" data-lang="{{ $code }}"></ul>
                                                </div>
                                            @endforeach
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="content_cookie" role="tabpanel">
                            <form method="POST" action="{{ route('admin.settings.store') }}">
                                @csrf
                                <input type="hidden" name="form" value="cookie">
                                <label for="">Display cookie consent for these countries</label>
                                <div class="row">
                                    @foreach(config('countries') as $code => $name)
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        id="cookie_country_{{ $code }}"
                                                        name="cookie_countries[]"
                                                        value="{{ $code }}"
                                                        {{ in_array($code, old('cookie_countries', json_decode(\App\Models\Option::get('cookie_countries', '[]'), true))) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="cookie_country_{{ $code }}">
                                                    <span class="flag-icon flag-icon-{{ strtolower($code) }}"></span>
                                                    {{ $name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                    @error('cookie_countries')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Save</button>
                            </form>
                        </div>
                        <div class="tab-pane" id="content_smtp" role="tabpanel">
                            <form method="POST" action="{{ route('admin.settings.store') }}">
                                @csrf
                                <input type="hidden" name="form" value="smtp">
                                <div class="form-group">
                                    <label for="from_address">From address</label>
                                    <input type="email" name="from_address" id="from_address"
                                           class="form-control @error('from_address') is-invalid @enderror"
                                           value="{{ old('from_address', \App\Models\Option::get('from_address')) }}">
                                    @error('from_address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="from_name">From name</label>
                                    <input type="text" name="from_name" id="from_name"
                                           class="form-control @error('from_name') is-invalid @enderror"
                                           value="{{ old('from_name', \App\Models\Option::get('from_name')) }}">
                                    @error('from_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="host">Host</label>
                                    <input type="text" name="host" id="host"
                                           class="form-control @error('host') is-invalid @enderror"
                                           value="{{ old('host', \App\Models\Option::get('host')) }}">
                                    @error('host')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="port">Port</label>
                                    <input type="text" name="port" id="port"
                                           class="form-control @error('port') is-invalid @enderror"
                                           value="{{ old('port', \App\Models\Option::get('port')) }}">
                                    @error('port')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="encryption">Encryption</label>
                                    <input type="text" name="encryption" id="encryption"
                                           class="form-control @error('encryption') is-invalid @enderror"
                                           value="{{ old('encryption', \App\Models\Option::get('encryption')) }}">
                                    @error('encryption')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" id="username"
                                           class="form-control @error('username') is-invalid @enderror"
                                           value="{{ old('username', \App\Models\Option::get('username')) }}">
                                    @error('username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="text" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           value="{{ old('password', \App\Models\Option::get('password')) }}">
                                    @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Save</button>
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <style>
        .menu-list li {
            list-style: none!important;
        }
    </style>
    <script>
        $(function () {
            let $menuLists = $('.menu-list');
            let $addItems = $('.add-item');
            $menuLists.sortable({
                placeholder: "ui-state-highlight"
            });

            $addItems.on('click', function () {
                const $box = $(this).parent().parent();
                const title = $box.find('input[name="title"]').val();
                const link = $box.find('input[name="link"]').val();
                const isBlank = $box.find('input[name="is_blank"]').is(':checked');

                const itemHtml = `
            <li class="list-group-item d-flex justify-content-between align-items-center menu-item">
              <div>
                <strong>${title}</strong> - ${link}
                ${isBlank ? '<span class="mr-1 badge bg-secondary">blank</span>' : ''}
              </div>
              <button class="btn btn-sm btn-danger remove-item"><i class="fas fa-trash"></i></button>
              <input type="hidden" name="title[]" value="${title}">
              <input type="hidden" name="link[]" value="${link}">
              <input type="hidden" name="is_blank[]" value="${isBlank}">
            </li>
          `;

                $menuLists.filter('[data-type="' + $(this).data('type') + '"][data-lang="' + $(this).data('lang') + '"]').first().append(itemHtml);

                $box.find('input[name="title"]').val('');
                $box.find('input[name="link"]').val('');
                $box.find('input[name="is_blank"]').prop('checked', false);
            });

            $menuLists.on('click', '.remove-item', function () {
                $(this).closest('li').remove();
            });

            $('.save-menu-form').on('submit', function (e) {
                e.preventDefault();
                let $form = $(this);

                $(this).find('.menu-list').each(function () {
                    const data = [];
                    let lang = $(this).closest('ul').data('lang');
                    let type = $(this).closest('ul').data('type');

                    $(this).find('li').each(function () {
                        data.push({
                            title: $(this).find('input[name="title[]"]').val(),
                            link: $(this).find('input[name="link[]"]').val(),
                            is_blank: $(this).find('input[name="is_blank[]"]').val() === 'true',
                        });
                    });

                    $form.find('[name="' + type + '_menu[' + lang + ']"]').val(JSON.stringify(data));
                });

                this.submit();
            });

            // Load data
            let headerMenu = @json(\App\Models\Option::get('header_menu'));
            let footerMenu = @json(\App\Models\Option::get('footer_menu'));
            loadData('header', headerMenu);
            loadData('footer', footerMenu);

            function loadData(type, menu) {
                let menuData = JSON.parse(menu);

                for (const lang in menuData) {
                    const raw = menuData[lang];
                    if (!raw) continue;

                    const items = JSON.parse(raw);

                    items.forEach(item => {
                        const itemHtml = `
    <li class="list-group-item d-flex justify-content-between align-items-center menu-item">
      <div>
        <strong>${item.title}</strong> - ${item.link}
        ${item.is_blank ? '<span class="mr-1 badge bg-secondary">blank</span>' : ''}
      </div>
      <button class="btn btn-sm btn-danger remove-item"><i class="fas fa-trash"></i></button>
      <input type="hidden" name="title[]" value="${item.title}">
      <input type="hidden" name="link[]" value="${item.link}">
      <input type="hidden" name="is_blank[]" value="${item.is_blank}">
    </li>
  `;

                        $('.menu-list[data-type="' + type + '"][data-lang="' + lang + '"]').append(itemHtml);
                    });
                }
            }

            const activeTab = @json(old('form', session('active_tab')));
            if (activeTab) {
                const tabId = '#tab_' + activeTab;
                const paneId = '#content_' + activeTab;

                $('a.nav-link').removeClass('active');
                $('.tab-pane').removeClass('show active');

                $(tabId).addClass('active');
                $(paneId).addClass('show active');
            }
        });
    </script>
@endsection
