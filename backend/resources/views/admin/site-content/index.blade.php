@extends('adminlte::page')

@section('title', 'Контент сайта')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Контент сайта
                </h1>
                <p class="text-muted mb-0 mt-1">Редактирование контента главной страницы, меню Header и Footer</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card card-modern">
        <div class="card-header-modern p-0 border-0">
            <ul class="nav nav-tabs-modern" id="site-content-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab_site_content" data-toggle="tab" href="#content_site_content" role="tab">
                        <i class="fas fa-file-alt mr-2"></i>Контент сайта
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab_header_menu" data-toggle="tab" href="#content_header_menu" role="tab">
                        <i class="fas fa-bars mr-2"></i>Меню Header
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab_footer_menu" data-toggle="tab" href="#content_footer_menu" role="tab">
                        <i class="fas fa-th mr-2"></i>Меню Footer
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                        <div class="tab-pane fade show active" id="content_site_content" role="tabpanel">
                            <form method="POST" action="{{ route('admin.site-content.store') }}">
                                @csrf
                                <input type="hidden" name="form" value="site_content">
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Информация:</strong> Здесь вы можете редактировать весь текстовый контент главной страницы сайта. 
                                    HTML теги поддерживаются. Для выделения текста градиентом используйте классы: 
                                    <code>gradient-text</code> (базовый градиент) или <code>gradient-text bg-gradient-1</code> (альтернативный градиент). 
                                    <strong>Пример:</strong> <code>Магазин &lt;span class="gradient-text"&gt;цифровых товаров&lt;/span&gt; и &lt;span class="gradient-text bg-gradient-1"&gt;премиум аккаунтов&lt;/span&gt;</code>
                                </div>

                                <!-- Currency -->
                                <div class="form-group mb-4">
                                    <label for="currency">Валюта</label>
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

                                <hr class="my-4">

                                <!-- Language Tabs -->
                                <ul class="nav nav-tabs" id="content-lang-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="lang-ru-tab" data-toggle="tab" href="#lang-ru" role="tab">
                                            🇷🇺 Русский
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="lang-en-tab" data-toggle="tab" href="#lang-en" role="tab">
                                            🇬🇧 English
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="lang-uk-tab" data-toggle="tab" href="#lang-uk" role="tab">
                                            🇺🇦 Українська
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3">
                                    <!-- RUSSIAN CONTENT -->
                                    <div class="tab-pane fade show active" id="lang-ru" role="tabpanel">
                                        <h5 class="mb-3">Hero секция (Главный заголовок)</h5>
                                        <div class="form-group">
                                            <label for="hero_title_ru">Заголовок *</label>
                                            <textarea name="hero_title_ru" id="hero_title_ru" rows="2"
                                                   class="form-control @error('hero_title_ru') is-invalid @enderror">{{ old('hero_title_ru', \App\Models\Option::get('hero_title_ru')) }}</textarea>
                                            @error('hero_title_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="hero_description_ru">Описание *</label>
                                            <textarea name="hero_description_ru" id="hero_description_ru" rows="3"
                                                   class="form-control @error('hero_description_ru') is-invalid @enderror">{{ old('hero_description_ru', \App\Models\Option::get('hero_description_ru')) }}</textarea>
                                            @error('hero_description_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="hero_button_ru">Текст кнопки *</label>
                                            <input type="text" name="hero_button_ru" id="hero_button_ru"
                                                   class="form-control @error('hero_button_ru') is-invalid @enderror"
                                                   value="{{ old('hero_button_ru', \App\Models\Option::get('hero_button_ru')) }}">
                                            @error('hero_button_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">О нас</h5>
                                        <div class="form-group">
                                            <label for="about_title_ru">Заголовок *</label>
                                            <input type="text" name="about_title_ru" id="about_title_ru"
                                                   class="form-control @error('about_title_ru') is-invalid @enderror"
                                                   value="{{ old('about_title_ru', \App\Models\Option::get('about_title_ru')) }}">
                                            @error('about_title_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="about_description_ru">Описание *</label>
                                            <textarea name="about_description_ru" id="about_description_ru" rows="4"
                                                   class="form-control @error('about_description_ru') is-invalid @enderror">{{ old('about_description_ru', \App\Models\Option::get('about_description_ru')) }}</textarea>
                                            @error('about_description_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Почему выбирают нашу платформу</h5>
                                        <div class="form-group">
                                            <label for="promote_title_ru">Заголовок секции *</label>
                                            <textarea name="promote_title_ru" id="promote_title_ru" rows="2"
                                                   class="form-control @error('promote_title_ru') is-invalid @enderror">{{ old('promote_title_ru', \App\Models\Option::get('promote_title_ru')) }}</textarea>
                                            @error('promote_title_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>1. Мгновенная доставка</h6>
                                                <div class="form-group">
                                                    <label for="promote_access_title_ru">Заголовок *</label>
                                                    <input type="text" name="promote_access_title_ru" id="promote_access_title_ru"
                                                           class="form-control" value="{{ old('promote_access_title_ru', \App\Models\Option::get('promote_access_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="promote_access_description_ru">Описание *</label>
                                                    <textarea name="promote_access_description_ru" id="promote_access_description_ru" rows="2"
                                                           class="form-control">{{ old('promote_access_description_ru', \App\Models\Option::get('promote_access_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>2. Лучшие цены</h6>
                                                <div class="form-group">
                                                    <label for="promote_pricing_title_ru">Заголовок *</label>
                                                    <input type="text" name="promote_pricing_title_ru" id="promote_pricing_title_ru"
                                                           class="form-control" value="{{ old('promote_pricing_title_ru', \App\Models\Option::get('promote_pricing_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="promote_pricing_description_ru">Описание *</label>
                                                    <textarea name="promote_pricing_description_ru" id="promote_pricing_description_ru" rows="2"
                                                           class="form-control">{{ old('promote_pricing_description_ru', \App\Models\Option::get('promote_pricing_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>3. Гарантия качества</h6>
                                                <div class="form-group">
                                                    <label for="promote_refund_title_ru">Заголовок *</label>
                                                    <input type="text" name="promote_refund_title_ru" id="promote_refund_title_ru"
                                                           class="form-control" value="{{ old('promote_refund_title_ru', \App\Models\Option::get('promote_refund_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="promote_refund_description_ru">Описание *</label>
                                                    <textarea name="promote_refund_description_ru" id="promote_refund_description_ru" rows="2"
                                                           class="form-control">{{ old('promote_refund_description_ru', \App\Models\Option::get('promote_refund_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>4. Проверенные товары</h6>
                                                <div class="form-group">
                                                    <label for="promote_activation_title_ru">Заголовок *</label>
                                                    <input type="text" name="promote_activation_title_ru" id="promote_activation_title_ru"
                                                           class="form-control" value="{{ old('promote_activation_title_ru', \App\Models\Option::get('promote_activation_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="promote_activation_description_ru">Описание *</label>
                                                    <textarea name="promote_activation_description_ru" id="promote_activation_description_ru" rows="2"
                                                           class="form-control">{{ old('promote_activation_description_ru', \App\Models\Option::get('promote_activation_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>5. Поддержка 24/7</h6>
                                                <div class="form-group">
                                                    <label for="promote_support_title_ru">Заголовок *</label>
                                                    <input type="text" name="promote_support_title_ru" id="promote_support_title_ru"
                                                           class="form-control" value="{{ old('promote_support_title_ru', \App\Models\Option::get('promote_support_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="promote_support_description_ru">Описание *</label>
                                                    <textarea name="promote_support_description_ru" id="promote_support_description_ru" rows="2"
                                                           class="form-control">{{ old('promote_support_description_ru', \App\Models\Option::get('promote_support_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>6. Безопасные платежи</h6>
                                                <div class="form-group">
                                                    <label for="promote_payment_title_ru">Заголовок *</label>
                                                    <input type="text" name="promote_payment_title_ru" id="promote_payment_title_ru"
                                                           class="form-control" value="{{ old('promote_payment_title_ru', \App\Models\Option::get('promote_payment_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="promote_payment_description_ru">Описание *</label>
                                                    <textarea name="promote_payment_description_ru" id="promote_payment_description_ru" rows="2"
                                                           class="form-control">{{ old('promote_payment_description_ru', \App\Models\Option::get('promote_payment_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Как купить товар в 3 шага</h5>
                                        <div class="form-group">
                                            <label for="steps_title_ru">Заголовок *</label>
                                            <textarea name="steps_title_ru" id="steps_title_ru" rows="2"
                                                   class="form-control @error('steps_title_ru') is-invalid @enderror">{{ old('steps_title_ru', \App\Models\Option::get('steps_title_ru')) }}</textarea>
                                            @error('steps_title_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="steps_description_ru">Подзаголовок *</label>
                                            <input type="text" name="steps_description_ru" id="steps_description_ru"
                                                   class="form-control @error('steps_description_ru') is-invalid @enderror"
                                                   value="{{ old('steps_description_ru', \App\Models\Option::get('steps_description_ru')) }}">
                                            @error('steps_description_ru')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Приветственный баннер</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_headline_ru">Заголовок</label>
                                            <textarea name="become_supplier_welcome_headline_ru" id="become_supplier_welcome_headline_ru" rows="2"
                                                   class="form-control">{{ old('become_supplier_welcome_headline_ru', \App\Models\Option::get('become_supplier_welcome_headline_ru')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_subtitle_ru">Подзаголовок</label>
                                            <textarea name="become_supplier_welcome_subtitle_ru" id="become_supplier_welcome_subtitle_ru" rows="2"
                                                   class="form-control">{{ old('become_supplier_welcome_subtitle_ru', \App\Models\Option::get('become_supplier_welcome_subtitle_ru')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_cta_ru">Текст кнопки</label>
                                            <input type="text" name="become_supplier_welcome_cta_ru" id="become_supplier_welcome_cta_ru"
                                                   class="form-control" value="{{ old('become_supplier_welcome_cta_ru', \App\Models\Option::get('become_supplier_welcome_cta_ru')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Статистика поставщиков</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_stats_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_stats_title_ru" id="become_supplier_stats_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_stats_title_ru', \App\Models\Option::get('become_supplier_stats_title_ru')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_active_suppliers_ru">Активных поставщиков</label>
                                                    <input type="text" name="become_supplier_stats_active_suppliers_ru" id="become_supplier_stats_active_suppliers_ru"
                                                           class="form-control" value="{{ old('become_supplier_stats_active_suppliers_ru', \App\Models\Option::get('become_supplier_stats_active_suppliers_ru')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_total_sales_ru">Всего продаж</label>
                                                    <input type="text" name="become_supplier_stats_total_sales_ru" id="become_supplier_stats_total_sales_ru"
                                                           class="form-control" value="{{ old('become_supplier_stats_total_sales_ru', \App\Models\Option::get('become_supplier_stats_total_sales_ru')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_average_rating_ru">Средний рейтинг</label>
                                                    <input type="text" name="become_supplier_stats_average_rating_ru" id="become_supplier_stats_average_rating_ru"
                                                           class="form-control" value="{{ old('become_supplier_stats_average_rating_ru', \App\Models\Option::get('become_supplier_stats_average_rating_ru')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_countries_ru">Стран</label>
                                                    <input type="text" name="become_supplier_stats_countries_ru" id="become_supplier_stats_countries_ru"
                                                           class="form-control" value="{{ old('become_supplier_stats_countries_ru', \App\Models\Option::get('become_supplier_stats_countries_ru')) }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Шаги процесса</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_process_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_process_title_ru" id="become_supplier_process_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_process_title_ru', \App\Models\Option::get('become_supplier_process_title_ru')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Шаг 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step1_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step1_title_ru" id="become_supplier_process_step1_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_process_step1_title_ru', \App\Models\Option::get('become_supplier_process_step1_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step1_description_ru">Описание</label>
                                                    <textarea name="become_supplier_process_step1_description_ru" id="become_supplier_process_step1_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step1_description_ru', \App\Models\Option::get('become_supplier_process_step1_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Шаг 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step2_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step2_title_ru" id="become_supplier_process_step2_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_process_step2_title_ru', \App\Models\Option::get('become_supplier_process_step2_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step2_description_ru">Описание</label>
                                                    <textarea name="become_supplier_process_step2_description_ru" id="become_supplier_process_step2_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step2_description_ru', \App\Models\Option::get('become_supplier_process_step2_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Шаг 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step3_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step3_title_ru" id="become_supplier_process_step3_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_process_step3_title_ru', \App\Models\Option::get('become_supplier_process_step3_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step3_description_ru">Описание</label>
                                                    <textarea name="become_supplier_process_step3_description_ru" id="become_supplier_process_step3_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step3_description_ru', \App\Models\Option::get('become_supplier_process_step3_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Шаг 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step4_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step4_title_ru" id="become_supplier_process_step4_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_process_step4_title_ru', \App\Models\Option::get('become_supplier_process_step4_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step4_description_ru">Описание</label>
                                                    <textarea name="become_supplier_process_step4_description_ru" id="become_supplier_process_step4_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step4_description_ru', \App\Models\Option::get('become_supplier_process_step4_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Категории цифровых товаров</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_categories_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_categories_title_ru" id="become_supplier_categories_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_categories_title_ru', \App\Models\Option::get('become_supplier_categories_title_ru')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_categories_subtitle_ru">Подзаголовок</label>
                                            <textarea name="become_supplier_categories_subtitle_ru" id="become_supplier_categories_subtitle_ru" rows="2"
                                                   class="form-control">{{ old('become_supplier_categories_subtitle_ru', \App\Models\Option::get('become_supplier_categories_subtitle_ru')) }}</textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_social_media_ru">Аккаунты соцсетей</label>
                                                    <input type="text" name="become_supplier_categories_social_media_ru" id="become_supplier_categories_social_media_ru"
                                                           class="form-control" value="{{ old('become_supplier_categories_social_media_ru', \App\Models\Option::get('become_supplier_categories_social_media_ru')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_gaming_ru">Игровые аккаунты</label>
                                                    <input type="text" name="become_supplier_categories_gaming_ru" id="become_supplier_categories_gaming_ru"
                                                           class="form-control" value="{{ old('become_supplier_categories_gaming_ru', \App\Models\Option::get('become_supplier_categories_gaming_ru')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_streaming_ru">Подписки на стриминги</label>
                                                    <input type="text" name="become_supplier_categories_streaming_ru" id="become_supplier_categories_streaming_ru"
                                                           class="form-control" value="{{ old('become_supplier_categories_streaming_ru', \App\Models\Option::get('become_supplier_categories_streaming_ru')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_software_ru">Лицензии ПО</label>
                                                    <input type="text" name="become_supplier_categories_software_ru" id="become_supplier_categories_software_ru"
                                                           class="form-control" value="{{ old('become_supplier_categories_software_ru', \App\Models\Option::get('become_supplier_categories_software_ru')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_other_ru">Другое</label>
                                                    <input type="text" name="become_supplier_categories_other_ru" id="become_supplier_categories_other_ru"
                                                           class="form-control" value="{{ old('become_supplier_categories_other_ru', \App\Models\Option::get('become_supplier_categories_other_ru')) }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Ограниченные товары</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_restricted_title_ru" id="become_supplier_restricted_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_restricted_title_ru', \App\Models\Option::get('become_supplier_restricted_title_ru')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_subtitle_ru">Подзаголовок</label>
                                            <textarea name="become_supplier_restricted_subtitle_ru" id="become_supplier_restricted_subtitle_ru" rows="2"
                                                   class="form-control">{{ old('become_supplier_restricted_subtitle_ru', \App\Models\Option::get('become_supplier_restricted_subtitle_ru')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_items_ru">Список товаров (каждый с новой строки)</label>
                                            <textarea name="become_supplier_restricted_items_ru" id="become_supplier_restricted_items_ru" rows="4"
                                                   class="form-control">{{ old('become_supplier_restricted_items_ru', \App\Models\Option::get('become_supplier_restricted_items_ru')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_contact_ru">Сообщение для связи</label>
                                            <textarea name="become_supplier_restricted_contact_ru" id="become_supplier_restricted_contact_ru" rows="2"
                                                   class="form-control">{{ old('become_supplier_restricted_contact_ru', \App\Models\Option::get('become_supplier_restricted_contact_ru')) }}</textarea>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Преимущества партнерства</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_benefits_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_benefits_title_ru" id="become_supplier_benefits_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_benefits_title_ru', \App\Models\Option::get('become_supplier_benefits_title_ru')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Преимущество 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit1_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit1_title_ru" id="become_supplier_benefits_benefit1_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit1_title_ru', \App\Models\Option::get('become_supplier_benefits_benefit1_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit1_description_ru">Описание</label>
                                                    <textarea name="become_supplier_benefits_benefit1_description_ru" id="become_supplier_benefits_benefit1_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit1_description_ru', \App\Models\Option::get('become_supplier_benefits_benefit1_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Преимущество 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit2_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit2_title_ru" id="become_supplier_benefits_benefit2_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit2_title_ru', \App\Models\Option::get('become_supplier_benefits_benefit2_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit2_description_ru">Описание</label>
                                                    <textarea name="become_supplier_benefits_benefit2_description_ru" id="become_supplier_benefits_benefit2_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit2_description_ru', \App\Models\Option::get('become_supplier_benefits_benefit2_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Преимущество 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit3_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit3_title_ru" id="become_supplier_benefits_benefit3_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit3_title_ru', \App\Models\Option::get('become_supplier_benefits_benefit3_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit3_description_ru">Описание</label>
                                                    <textarea name="become_supplier_benefits_benefit3_description_ru" id="become_supplier_benefits_benefit3_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit3_description_ru', \App\Models\Option::get('become_supplier_benefits_benefit3_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Преимущество 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit4_title_ru">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit4_title_ru" id="become_supplier_benefits_benefit4_title_ru"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit4_title_ru', \App\Models\Option::get('become_supplier_benefits_benefit4_title_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit4_description_ru">Описание</label>
                                                    <textarea name="become_supplier_benefits_benefit4_description_ru" id="become_supplier_benefits_benefit4_description_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit4_description_ru', \App\Models\Option::get('become_supplier_benefits_benefit4_description_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - Способы вывода средств</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_payout_title_ru" id="become_supplier_payout_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_payout_title_ru', \App\Models\Option::get('become_supplier_payout_title_ru')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_subtitle_ru">Подзаголовок</label>
                                            <textarea name="become_supplier_payout_subtitle_ru" id="become_supplier_payout_subtitle_ru" rows="2"
                                                   class="form-control">{{ old('become_supplier_payout_subtitle_ru', \App\Models\Option::get('become_supplier_payout_subtitle_ru')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_methods_ru">Способы вывода (каждый с новой строки)</label>
                                            <textarea name="become_supplier_payout_methods_ru" id="become_supplier_payout_methods_ru" rows="3"
                                                   class="form-control">{{ old('become_supplier_payout_methods_ru', \App\Models\Option::get('become_supplier_payout_methods_ru')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_cta_ru">Текст кнопки</label>
                                            <input type="text" name="become_supplier_payout_cta_ru" id="become_supplier_payout_cta_ru"
                                                   class="form-control" value="{{ old('become_supplier_payout_cta_ru', \App\Models\Option::get('become_supplier_payout_cta_ru')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стать поставщиком - FAQ</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_faq_title_ru">Заголовок</label>
                                            <input type="text" name="become_supplier_faq_title_ru" id="become_supplier_faq_title_ru"
                                                   class="form-control" value="{{ old('become_supplier_faq_title_ru', \App\Models\Option::get('become_supplier_faq_title_ru')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Вопрос 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question1_ru">Вопрос</label>
                                                    <input type="text" name="become_supplier_faq_question1_ru" id="become_supplier_faq_question1_ru"
                                                           class="form-control" value="{{ old('become_supplier_faq_question1_ru', \App\Models\Option::get('become_supplier_faq_question1_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer1_ru">Ответ</label>
                                                    <textarea name="become_supplier_faq_answer1_ru" id="become_supplier_faq_answer1_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer1_ru', \App\Models\Option::get('become_supplier_faq_answer1_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Вопрос 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question2_ru">Вопрос</label>
                                                    <input type="text" name="become_supplier_faq_question2_ru" id="become_supplier_faq_question2_ru"
                                                           class="form-control" value="{{ old('become_supplier_faq_question2_ru', \App\Models\Option::get('become_supplier_faq_question2_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer2_ru">Ответ</label>
                                                    <textarea name="become_supplier_faq_answer2_ru" id="become_supplier_faq_answer2_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer2_ru', \App\Models\Option::get('become_supplier_faq_answer2_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Вопрос 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question3_ru">Вопрос</label>
                                                    <input type="text" name="become_supplier_faq_question3_ru" id="become_supplier_faq_question3_ru"
                                                           class="form-control" value="{{ old('become_supplier_faq_question3_ru', \App\Models\Option::get('become_supplier_faq_question3_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer3_ru">Ответ</label>
                                                    <textarea name="become_supplier_faq_answer3_ru" id="become_supplier_faq_answer3_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer3_ru', \App\Models\Option::get('become_supplier_faq_answer3_ru')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Вопрос 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question4_ru">Вопрос</label>
                                                    <input type="text" name="become_supplier_faq_question4_ru" id="become_supplier_faq_question4_ru"
                                                           class="form-control" value="{{ old('become_supplier_faq_question4_ru', \App\Models\Option::get('become_supplier_faq_question4_ru')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer4_ru">Ответ</label>
                                                    <textarea name="become_supplier_faq_answer4_ru" id="become_supplier_faq_answer4_ru" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer4_ru', \App\Models\Option::get('become_supplier_faq_answer4_ru')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ENGLISH CONTENT -->
                                    <div class="tab-pane fade" id="lang-en" role="tabpanel">
                                        <h5 class="mb-3">Hero Section</h5>
                                        <div class="form-group">
                                            <label for="hero_title_en">Title</label>
                                            <textarea name="hero_title_en" id="hero_title_en" rows="2"
                                                   class="form-control">{{ old('hero_title_en', \App\Models\Option::get('hero_title_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="hero_description_en">Description</label>
                                            <textarea name="hero_description_en" id="hero_description_en" rows="3"
                                                   class="form-control">{{ old('hero_description_en', \App\Models\Option::get('hero_description_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="hero_button_en">Button Text</label>
                                            <input type="text" name="hero_button_en" id="hero_button_en"
                                                   class="form-control" value="{{ old('hero_button_en', \App\Models\Option::get('hero_button_en')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">About Us</h5>
                                        <div class="form-group">
                                            <label for="about_title_en">Title</label>
                                            <input type="text" name="about_title_en" id="about_title_en"
                                                   class="form-control" value="{{ old('about_title_en', \App\Models\Option::get('about_title_en')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="about_description_en">Description</label>
                                            <textarea name="about_description_en" id="about_description_en" rows="4"
                                                   class="form-control">{{ old('about_description_en', \App\Models\Option::get('about_description_en')) }}</textarea>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Why Choose Our Platform</h5>
                                        <div class="form-group">
                                            <label for="promote_title_en">Section Title</label>
                                            <textarea name="promote_title_en" id="promote_title_en" rows="2"
                                                   class="form-control">{{ old('promote_title_en', \App\Models\Option::get('promote_title_en')) }}</textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>1. Instant Delivery</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_access_title_en" placeholder="Title"
                                                           class="form-control" value="{{ old('promote_access_title_en', \App\Models\Option::get('promote_access_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_access_description_en" rows="2" placeholder="Description"
                                                           class="form-control">{{ old('promote_access_description_en', \App\Models\Option::get('promote_access_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>2. Best Prices</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_pricing_title_en" placeholder="Title"
                                                           class="form-control" value="{{ old('promote_pricing_title_en', \App\Models\Option::get('promote_pricing_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_pricing_description_en" rows="2" placeholder="Description"
                                                           class="form-control">{{ old('promote_pricing_description_en', \App\Models\Option::get('promote_pricing_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>3. Quality Guarantee</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_refund_title_en" placeholder="Title"
                                                           class="form-control" value="{{ old('promote_refund_title_en', \App\Models\Option::get('promote_refund_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_refund_description_en" rows="2" placeholder="Description"
                                                           class="form-control">{{ old('promote_refund_description_en', \App\Models\Option::get('promote_refund_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>4. Verified Products</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_activation_title_en" placeholder="Title"
                                                           class="form-control" value="{{ old('promote_activation_title_en', \App\Models\Option::get('promote_activation_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_activation_description_en" rows="2" placeholder="Description"
                                                           class="form-control">{{ old('promote_activation_description_en', \App\Models\Option::get('promote_activation_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>5. 24/7 Support</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_support_title_en" placeholder="Title"
                                                           class="form-control" value="{{ old('promote_support_title_en', \App\Models\Option::get('promote_support_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_support_description_en" rows="2" placeholder="Description"
                                                           class="form-control">{{ old('promote_support_description_en', \App\Models\Option::get('promote_support_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>6. Secure Payments</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_payment_title_en" placeholder="Title"
                                                           class="form-control" value="{{ old('promote_payment_title_en', \App\Models\Option::get('promote_payment_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_payment_description_en" rows="2" placeholder="Description"
                                                           class="form-control">{{ old('promote_payment_description_en', \App\Models\Option::get('promote_payment_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">How to Buy in 3 Steps</h5>
                                        <div class="form-group">
                                            <label for="steps_title_en">Title</label>
                                            <textarea name="steps_title_en" id="steps_title_en" rows="2"
                                                   class="form-control">{{ old('steps_title_en', \App\Models\Option::get('steps_title_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="steps_description_en">Subtitle</label>
                                            <input type="text" name="steps_description_en" id="steps_description_en"
                                                   class="form-control" value="{{ old('steps_description_en', \App\Models\Option::get('steps_description_en')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Welcome Banner</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_headline_en">Headline</label>
                                            <textarea name="become_supplier_welcome_headline_en" id="become_supplier_welcome_headline_en" rows="2"
                                                   class="form-control">{{ old('become_supplier_welcome_headline_en', \App\Models\Option::get('become_supplier_welcome_headline_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_subtitle_en">Subtitle</label>
                                            <textarea name="become_supplier_welcome_subtitle_en" id="become_supplier_welcome_subtitle_en" rows="2"
                                                   class="form-control">{{ old('become_supplier_welcome_subtitle_en', \App\Models\Option::get('become_supplier_welcome_subtitle_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_cta_en">CTA Button Text</label>
                                            <input type="text" name="become_supplier_welcome_cta_en" id="become_supplier_welcome_cta_en"
                                                   class="form-control" value="{{ old('become_supplier_welcome_cta_en', \App\Models\Option::get('become_supplier_welcome_cta_en')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Supplier Stats</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_stats_title_en">Title</label>
                                            <input type="text" name="become_supplier_stats_title_en" id="become_supplier_stats_title_en"
                                                   class="form-control" value="{{ old('become_supplier_stats_title_en', \App\Models\Option::get('become_supplier_stats_title_en')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_active_suppliers_en">Active Suppliers</label>
                                                    <input type="text" name="become_supplier_stats_active_suppliers_en" id="become_supplier_stats_active_suppliers_en"
                                                           class="form-control" value="{{ old('become_supplier_stats_active_suppliers_en', \App\Models\Option::get('become_supplier_stats_active_suppliers_en')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_total_sales_en">Total Sales</label>
                                                    <input type="text" name="become_supplier_stats_total_sales_en" id="become_supplier_stats_total_sales_en"
                                                           class="form-control" value="{{ old('become_supplier_stats_total_sales_en', \App\Models\Option::get('become_supplier_stats_total_sales_en')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_average_rating_en">Average Rating</label>
                                                    <input type="text" name="become_supplier_stats_average_rating_en" id="become_supplier_stats_average_rating_en"
                                                           class="form-control" value="{{ old('become_supplier_stats_average_rating_en', \App\Models\Option::get('become_supplier_stats_average_rating_en')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_countries_en">Countries</label>
                                                    <input type="text" name="become_supplier_stats_countries_en" id="become_supplier_stats_countries_en"
                                                           class="form-control" value="{{ old('become_supplier_stats_countries_en', \App\Models\Option::get('become_supplier_stats_countries_en')) }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Process Steps</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_process_title_en">Title</label>
                                            <input type="text" name="become_supplier_process_title_en" id="become_supplier_process_title_en"
                                                   class="form-control" value="{{ old('become_supplier_process_title_en', \App\Models\Option::get('become_supplier_process_title_en')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Step 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step1_title_en">Title</label>
                                                    <input type="text" name="become_supplier_process_step1_title_en" id="become_supplier_process_step1_title_en"
                                                           class="form-control" value="{{ old('become_supplier_process_step1_title_en', \App\Models\Option::get('become_supplier_process_step1_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step1_description_en">Description</label>
                                                    <textarea name="become_supplier_process_step1_description_en" id="become_supplier_process_step1_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step1_description_en', \App\Models\Option::get('become_supplier_process_step1_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Step 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step2_title_en">Title</label>
                                                    <input type="text" name="become_supplier_process_step2_title_en" id="become_supplier_process_step2_title_en"
                                                           class="form-control" value="{{ old('become_supplier_process_step2_title_en', \App\Models\Option::get('become_supplier_process_step2_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step2_description_en">Description</label>
                                                    <textarea name="become_supplier_process_step2_description_en" id="become_supplier_process_step2_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step2_description_en', \App\Models\Option::get('become_supplier_process_step2_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Step 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step3_title_en">Title</label>
                                                    <input type="text" name="become_supplier_process_step3_title_en" id="become_supplier_process_step3_title_en"
                                                           class="form-control" value="{{ old('become_supplier_process_step3_title_en', \App\Models\Option::get('become_supplier_process_step3_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step3_description_en">Description</label>
                                                    <textarea name="become_supplier_process_step3_description_en" id="become_supplier_process_step3_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step3_description_en', \App\Models\Option::get('become_supplier_process_step3_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Step 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step4_title_en">Title</label>
                                                    <input type="text" name="become_supplier_process_step4_title_en" id="become_supplier_process_step4_title_en"
                                                           class="form-control" value="{{ old('become_supplier_process_step4_title_en', \App\Models\Option::get('become_supplier_process_step4_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step4_description_en">Description</label>
                                                    <textarea name="become_supplier_process_step4_description_en" id="become_supplier_process_step4_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step4_description_en', \App\Models\Option::get('become_supplier_process_step4_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Digital Goods Categories</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_categories_title_en">Title</label>
                                            <input type="text" name="become_supplier_categories_title_en" id="become_supplier_categories_title_en"
                                                   class="form-control" value="{{ old('become_supplier_categories_title_en', \App\Models\Option::get('become_supplier_categories_title_en')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_categories_subtitle_en">Subtitle</label>
                                            <textarea name="become_supplier_categories_subtitle_en" id="become_supplier_categories_subtitle_en" rows="2"
                                                   class="form-control">{{ old('become_supplier_categories_subtitle_en', \App\Models\Option::get('become_supplier_categories_subtitle_en')) }}</textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_social_media_en">Social Media Accounts</label>
                                                    <input type="text" name="become_supplier_categories_social_media_en" id="become_supplier_categories_social_media_en"
                                                           class="form-control" value="{{ old('become_supplier_categories_social_media_en', \App\Models\Option::get('become_supplier_categories_social_media_en')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_gaming_en">Gaming Accounts</label>
                                                    <input type="text" name="become_supplier_categories_gaming_en" id="become_supplier_categories_gaming_en"
                                                           class="form-control" value="{{ old('become_supplier_categories_gaming_en', \App\Models\Option::get('become_supplier_categories_gaming_en')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_streaming_en">Streaming Subscriptions</label>
                                                    <input type="text" name="become_supplier_categories_streaming_en" id="become_supplier_categories_streaming_en"
                                                           class="form-control" value="{{ old('become_supplier_categories_streaming_en', \App\Models\Option::get('become_supplier_categories_streaming_en')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_software_en">Software Licenses</label>
                                                    <input type="text" name="become_supplier_categories_software_en" id="become_supplier_categories_software_en"
                                                           class="form-control" value="{{ old('become_supplier_categories_software_en', \App\Models\Option::get('become_supplier_categories_software_en')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_other_en">Other</label>
                                                    <input type="text" name="become_supplier_categories_other_en" id="become_supplier_categories_other_en"
                                                           class="form-control" value="{{ old('become_supplier_categories_other_en', \App\Models\Option::get('become_supplier_categories_other_en')) }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Restricted Items</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_title_en">Title</label>
                                            <input type="text" name="become_supplier_restricted_title_en" id="become_supplier_restricted_title_en"
                                                   class="form-control" value="{{ old('become_supplier_restricted_title_en', \App\Models\Option::get('become_supplier_restricted_title_en')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_subtitle_en">Subtitle</label>
                                            <textarea name="become_supplier_restricted_subtitle_en" id="become_supplier_restricted_subtitle_en" rows="2"
                                                   class="form-control">{{ old('become_supplier_restricted_subtitle_en', \App\Models\Option::get('become_supplier_restricted_subtitle_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_items_en">Items List (one per line)</label>
                                            <textarea name="become_supplier_restricted_items_en" id="become_supplier_restricted_items_en" rows="4"
                                                   class="form-control">{{ old('become_supplier_restricted_items_en', \App\Models\Option::get('become_supplier_restricted_items_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_contact_en">Contact Message</label>
                                            <textarea name="become_supplier_restricted_contact_en" id="become_supplier_restricted_contact_en" rows="2"
                                                   class="form-control">{{ old('become_supplier_restricted_contact_en', \App\Models\Option::get('become_supplier_restricted_contact_en')) }}</textarea>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Partner Benefits</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_benefits_title_en">Title</label>
                                            <input type="text" name="become_supplier_benefits_title_en" id="become_supplier_benefits_title_en"
                                                   class="form-control" value="{{ old('become_supplier_benefits_title_en', \App\Models\Option::get('become_supplier_benefits_title_en')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Benefit 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit1_title_en">Title</label>
                                                    <input type="text" name="become_supplier_benefits_benefit1_title_en" id="become_supplier_benefits_benefit1_title_en"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit1_title_en', \App\Models\Option::get('become_supplier_benefits_benefit1_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit1_description_en">Description</label>
                                                    <textarea name="become_supplier_benefits_benefit1_description_en" id="become_supplier_benefits_benefit1_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit1_description_en', \App\Models\Option::get('become_supplier_benefits_benefit1_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Benefit 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit2_title_en">Title</label>
                                                    <input type="text" name="become_supplier_benefits_benefit2_title_en" id="become_supplier_benefits_benefit2_title_en"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit2_title_en', \App\Models\Option::get('become_supplier_benefits_benefit2_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit2_description_en">Description</label>
                                                    <textarea name="become_supplier_benefits_benefit2_description_en" id="become_supplier_benefits_benefit2_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit2_description_en', \App\Models\Option::get('become_supplier_benefits_benefit2_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Benefit 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit3_title_en">Title</label>
                                                    <input type="text" name="become_supplier_benefits_benefit3_title_en" id="become_supplier_benefits_benefit3_title_en"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit3_title_en', \App\Models\Option::get('become_supplier_benefits_benefit3_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit3_description_en">Description</label>
                                                    <textarea name="become_supplier_benefits_benefit3_description_en" id="become_supplier_benefits_benefit3_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit3_description_en', \App\Models\Option::get('become_supplier_benefits_benefit3_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Benefit 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit4_title_en">Title</label>
                                                    <input type="text" name="become_supplier_benefits_benefit4_title_en" id="become_supplier_benefits_benefit4_title_en"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit4_title_en', \App\Models\Option::get('become_supplier_benefits_benefit4_title_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit4_description_en">Description</label>
                                                    <textarea name="become_supplier_benefits_benefit4_description_en" id="become_supplier_benefits_benefit4_description_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit4_description_en', \App\Models\Option::get('become_supplier_benefits_benefit4_description_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - Payout Methods</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_title_en">Title</label>
                                            <input type="text" name="become_supplier_payout_title_en" id="become_supplier_payout_title_en"
                                                   class="form-control" value="{{ old('become_supplier_payout_title_en', \App\Models\Option::get('become_supplier_payout_title_en')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_subtitle_en">Subtitle</label>
                                            <textarea name="become_supplier_payout_subtitle_en" id="become_supplier_payout_subtitle_en" rows="2"
                                                   class="form-control">{{ old('become_supplier_payout_subtitle_en', \App\Models\Option::get('become_supplier_payout_subtitle_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_methods_en">Payout Methods (one per line)</label>
                                            <textarea name="become_supplier_payout_methods_en" id="become_supplier_payout_methods_en" rows="3"
                                                   class="form-control">{{ old('become_supplier_payout_methods_en', \App\Models\Option::get('become_supplier_payout_methods_en')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_cta_en">CTA Button Text</label>
                                            <input type="text" name="become_supplier_payout_cta_en" id="become_supplier_payout_cta_en"
                                                   class="form-control" value="{{ old('become_supplier_payout_cta_en', \App\Models\Option::get('become_supplier_payout_cta_en')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Become Supplier - FAQ</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_faq_title_en">Title</label>
                                            <input type="text" name="become_supplier_faq_title_en" id="become_supplier_faq_title_en"
                                                   class="form-control" value="{{ old('become_supplier_faq_title_en', \App\Models\Option::get('become_supplier_faq_title_en')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Question 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question1_en">Question</label>
                                                    <input type="text" name="become_supplier_faq_question1_en" id="become_supplier_faq_question1_en"
                                                           class="form-control" value="{{ old('become_supplier_faq_question1_en', \App\Models\Option::get('become_supplier_faq_question1_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer1_en">Answer</label>
                                                    <textarea name="become_supplier_faq_answer1_en" id="become_supplier_faq_answer1_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer1_en', \App\Models\Option::get('become_supplier_faq_answer1_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Question 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question2_en">Question</label>
                                                    <input type="text" name="become_supplier_faq_question2_en" id="become_supplier_faq_question2_en"
                                                           class="form-control" value="{{ old('become_supplier_faq_question2_en', \App\Models\Option::get('become_supplier_faq_question2_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer2_en">Answer</label>
                                                    <textarea name="become_supplier_faq_answer2_en" id="become_supplier_faq_answer2_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer2_en', \App\Models\Option::get('become_supplier_faq_answer2_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Question 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question3_en">Question</label>
                                                    <input type="text" name="become_supplier_faq_question3_en" id="become_supplier_faq_question3_en"
                                                           class="form-control" value="{{ old('become_supplier_faq_question3_en', \App\Models\Option::get('become_supplier_faq_question3_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer3_en">Answer</label>
                                                    <textarea name="become_supplier_faq_answer3_en" id="become_supplier_faq_answer3_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer3_en', \App\Models\Option::get('become_supplier_faq_answer3_en')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Question 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question4_en">Question</label>
                                                    <input type="text" name="become_supplier_faq_question4_en" id="become_supplier_faq_question4_en"
                                                           class="form-control" value="{{ old('become_supplier_faq_question4_en', \App\Models\Option::get('become_supplier_faq_question4_en')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer4_en">Answer</label>
                                                    <textarea name="become_supplier_faq_answer4_en" id="become_supplier_faq_answer4_en" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer4_en', \App\Models\Option::get('become_supplier_faq_answer4_en')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- UKRAINIAN CONTENT -->
                                    <div class="tab-pane fade" id="lang-uk" role="tabpanel">
                                        <h5 class="mb-3">Hero секція</h5>
                                        <div class="form-group">
                                            <label for="hero_title_uk">Заголовок</label>
                                            <textarea name="hero_title_uk" id="hero_title_uk" rows="2"
                                                   class="form-control">{{ old('hero_title_uk', \App\Models\Option::get('hero_title_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="hero_description_uk">Опис</label>
                                            <textarea name="hero_description_uk" id="hero_description_uk" rows="3"
                                                   class="form-control">{{ old('hero_description_uk', \App\Models\Option::get('hero_description_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="hero_button_uk">Текст кнопки</label>
                                            <input type="text" name="hero_button_uk" id="hero_button_uk"
                                                   class="form-control" value="{{ old('hero_button_uk', \App\Models\Option::get('hero_button_uk')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Про нас</h5>
                                        <div class="form-group">
                                            <label for="about_title_uk">Заголовок</label>
                                            <input type="text" name="about_title_uk" id="about_title_uk"
                                                   class="form-control" value="{{ old('about_title_uk', \App\Models\Option::get('about_title_uk')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="about_description_uk">Опис</label>
                                            <textarea name="about_description_uk" id="about_description_uk" rows="4"
                                                   class="form-control">{{ old('about_description_uk', \App\Models\Option::get('about_description_uk')) }}</textarea>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Чому обирають нашу платформу</h5>
                                        <div class="form-group">
                                            <label for="promote_title_uk">Заголовок секції</label>
                                            <textarea name="promote_title_uk" id="promote_title_uk" rows="2"
                                                   class="form-control">{{ old('promote_title_uk', \App\Models\Option::get('promote_title_uk')) }}</textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>1. Миттєва доставка</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_access_title_uk" placeholder="Заголовок"
                                                           class="form-control" value="{{ old('promote_access_title_uk', \App\Models\Option::get('promote_access_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_access_description_uk" rows="2" placeholder="Опис"
                                                           class="form-control">{{ old('promote_access_description_uk', \App\Models\Option::get('promote_access_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>2. Найкращі ціни</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_pricing_title_uk" placeholder="Заголовок"
                                                           class="form-control" value="{{ old('promote_pricing_title_uk', \App\Models\Option::get('promote_pricing_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_pricing_description_uk" rows="2" placeholder="Опис"
                                                           class="form-control">{{ old('promote_pricing_description_uk', \App\Models\Option::get('promote_pricing_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>3. Гарантія якості</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_refund_title_uk" placeholder="Заголовок"
                                                           class="form-control" value="{{ old('promote_refund_title_uk', \App\Models\Option::get('promote_refund_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_refund_description_uk" rows="2" placeholder="Опис"
                                                           class="form-control">{{ old('promote_refund_description_uk', \App\Models\Option::get('promote_refund_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>4. Перевірені товари</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_activation_title_uk" placeholder="Заголовок"
                                                           class="form-control" value="{{ old('promote_activation_title_uk', \App\Models\Option::get('promote_activation_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_activation_description_uk" rows="2" placeholder="Опис"
                                                           class="form-control">{{ old('promote_activation_description_uk', \App\Models\Option::get('promote_activation_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>5. Підтримка 24/7</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_support_title_uk" placeholder="Заголовок"
                                                           class="form-control" value="{{ old('promote_support_title_uk', \App\Models\Option::get('promote_support_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_support_description_uk" rows="2" placeholder="Опис"
                                                           class="form-control">{{ old('promote_support_description_uk', \App\Models\Option::get('promote_support_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>6. Безпечні платежі</h6>
                                                <div class="form-group">
                                                    <input type="text" name="promote_payment_title_uk" placeholder="Заголовок"
                                                           class="form-control" value="{{ old('promote_payment_title_uk', \App\Models\Option::get('promote_payment_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="promote_payment_description_uk" rows="2" placeholder="Опис"
                                                           class="form-control">{{ old('promote_payment_description_uk', \App\Models\Option::get('promote_payment_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Як купити товар у 3 кроки</h5>
                                        <div class="form-group">
                                            <label for="steps_title_uk">Заголовок</label>
                                            <textarea name="steps_title_uk" id="steps_title_uk" rows="2"
                                                   class="form-control">{{ old('steps_title_uk', \App\Models\Option::get('steps_title_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="steps_description_uk">Підзаголовок</label>
                                            <input type="text" name="steps_description_uk" id="steps_description_uk"
                                                   class="form-control" value="{{ old('steps_description_uk', \App\Models\Option::get('steps_description_uk')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Привітальний банер</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_headline_uk">Заголовок</label>
                                            <textarea name="become_supplier_welcome_headline_uk" id="become_supplier_welcome_headline_uk" rows="2"
                                                   class="form-control">{{ old('become_supplier_welcome_headline_uk', \App\Models\Option::get('become_supplier_welcome_headline_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_subtitle_uk">Підзаголовок</label>
                                            <textarea name="become_supplier_welcome_subtitle_uk" id="become_supplier_welcome_subtitle_uk" rows="2"
                                                   class="form-control">{{ old('become_supplier_welcome_subtitle_uk', \App\Models\Option::get('become_supplier_welcome_subtitle_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_welcome_cta_uk">Текст кнопки</label>
                                            <input type="text" name="become_supplier_welcome_cta_uk" id="become_supplier_welcome_cta_uk"
                                                   class="form-control" value="{{ old('become_supplier_welcome_cta_uk', \App\Models\Option::get('become_supplier_welcome_cta_uk')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Статистика постачальників</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_stats_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_stats_title_uk" id="become_supplier_stats_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_stats_title_uk', \App\Models\Option::get('become_supplier_stats_title_uk')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_active_suppliers_uk">Активних постачальників</label>
                                                    <input type="text" name="become_supplier_stats_active_suppliers_uk" id="become_supplier_stats_active_suppliers_uk"
                                                           class="form-control" value="{{ old('become_supplier_stats_active_suppliers_uk', \App\Models\Option::get('become_supplier_stats_active_suppliers_uk')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_total_sales_uk">Всього продажів</label>
                                                    <input type="text" name="become_supplier_stats_total_sales_uk" id="become_supplier_stats_total_sales_uk"
                                                           class="form-control" value="{{ old('become_supplier_stats_total_sales_uk', \App\Models\Option::get('become_supplier_stats_total_sales_uk')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_average_rating_uk">Середній рейтинг</label>
                                                    <input type="text" name="become_supplier_stats_average_rating_uk" id="become_supplier_stats_average_rating_uk"
                                                           class="form-control" value="{{ old('become_supplier_stats_average_rating_uk', \App\Models\Option::get('become_supplier_stats_average_rating_uk')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_stats_countries_uk">Країн</label>
                                                    <input type="text" name="become_supplier_stats_countries_uk" id="become_supplier_stats_countries_uk"
                                                           class="form-control" value="{{ old('become_supplier_stats_countries_uk', \App\Models\Option::get('become_supplier_stats_countries_uk')) }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Кроки процесу</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_process_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_process_title_uk" id="become_supplier_process_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_process_title_uk', \App\Models\Option::get('become_supplier_process_title_uk')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Крок 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step1_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step1_title_uk" id="become_supplier_process_step1_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_process_step1_title_uk', \App\Models\Option::get('become_supplier_process_step1_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step1_description_uk">Опис</label>
                                                    <textarea name="become_supplier_process_step1_description_uk" id="become_supplier_process_step1_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step1_description_uk', \App\Models\Option::get('become_supplier_process_step1_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Крок 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step2_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step2_title_uk" id="become_supplier_process_step2_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_process_step2_title_uk', \App\Models\Option::get('become_supplier_process_step2_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step2_description_uk">Опис</label>
                                                    <textarea name="become_supplier_process_step2_description_uk" id="become_supplier_process_step2_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step2_description_uk', \App\Models\Option::get('become_supplier_process_step2_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Крок 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step3_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step3_title_uk" id="become_supplier_process_step3_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_process_step3_title_uk', \App\Models\Option::get('become_supplier_process_step3_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step3_description_uk">Опис</label>
                                                    <textarea name="become_supplier_process_step3_description_uk" id="become_supplier_process_step3_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step3_description_uk', \App\Models\Option::get('become_supplier_process_step3_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Крок 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step4_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_process_step4_title_uk" id="become_supplier_process_step4_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_process_step4_title_uk', \App\Models\Option::get('become_supplier_process_step4_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_process_step4_description_uk">Опис</label>
                                                    <textarea name="become_supplier_process_step4_description_uk" id="become_supplier_process_step4_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_process_step4_description_uk', \App\Models\Option::get('become_supplier_process_step4_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Категорії цифрових товарів</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_categories_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_categories_title_uk" id="become_supplier_categories_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_categories_title_uk', \App\Models\Option::get('become_supplier_categories_title_uk')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_categories_subtitle_uk">Підзаголовок</label>
                                            <textarea name="become_supplier_categories_subtitle_uk" id="become_supplier_categories_subtitle_uk" rows="2"
                                                   class="form-control">{{ old('become_supplier_categories_subtitle_uk', \App\Models\Option::get('become_supplier_categories_subtitle_uk')) }}</textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_social_media_uk">Акаунти соцмереж</label>
                                                    <input type="text" name="become_supplier_categories_social_media_uk" id="become_supplier_categories_social_media_uk"
                                                           class="form-control" value="{{ old('become_supplier_categories_social_media_uk', \App\Models\Option::get('become_supplier_categories_social_media_uk')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_gaming_uk">Ігрові акаунти</label>
                                                    <input type="text" name="become_supplier_categories_gaming_uk" id="become_supplier_categories_gaming_uk"
                                                           class="form-control" value="{{ old('become_supplier_categories_gaming_uk', \App\Models\Option::get('become_supplier_categories_gaming_uk')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_streaming_uk">Підписки на стрімінги</label>
                                                    <input type="text" name="become_supplier_categories_streaming_uk" id="become_supplier_categories_streaming_uk"
                                                           class="form-control" value="{{ old('become_supplier_categories_streaming_uk', \App\Models\Option::get('become_supplier_categories_streaming_uk')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_software_uk">Ліцензії ПЗ</label>
                                                    <input type="text" name="become_supplier_categories_software_uk" id="become_supplier_categories_software_uk"
                                                           class="form-control" value="{{ old('become_supplier_categories_software_uk', \App\Models\Option::get('become_supplier_categories_software_uk')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="become_supplier_categories_other_uk">Інше</label>
                                                    <input type="text" name="become_supplier_categories_other_uk" id="become_supplier_categories_other_uk"
                                                           class="form-control" value="{{ old('become_supplier_categories_other_uk', \App\Models\Option::get('become_supplier_categories_other_uk')) }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Обмежені товари</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_restricted_title_uk" id="become_supplier_restricted_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_restricted_title_uk', \App\Models\Option::get('become_supplier_restricted_title_uk')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_subtitle_uk">Підзаголовок</label>
                                            <textarea name="become_supplier_restricted_subtitle_uk" id="become_supplier_restricted_subtitle_uk" rows="2"
                                                   class="form-control">{{ old('become_supplier_restricted_subtitle_uk', \App\Models\Option::get('become_supplier_restricted_subtitle_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_items_uk">Список товарів (кожен з нового рядка)</label>
                                            <textarea name="become_supplier_restricted_items_uk" id="become_supplier_restricted_items_uk" rows="4"
                                                   class="form-control">{{ old('become_supplier_restricted_items_uk', \App\Models\Option::get('become_supplier_restricted_items_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_restricted_contact_uk">Повідомлення для зв'язку</label>
                                            <textarea name="become_supplier_restricted_contact_uk" id="become_supplier_restricted_contact_uk" rows="2"
                                                   class="form-control">{{ old('become_supplier_restricted_contact_uk', \App\Models\Option::get('become_supplier_restricted_contact_uk')) }}</textarea>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Переваги партнерства</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_benefits_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_benefits_title_uk" id="become_supplier_benefits_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_benefits_title_uk', \App\Models\Option::get('become_supplier_benefits_title_uk')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Перевага 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit1_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit1_title_uk" id="become_supplier_benefits_benefit1_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit1_title_uk', \App\Models\Option::get('become_supplier_benefits_benefit1_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit1_description_uk">Опис</label>
                                                    <textarea name="become_supplier_benefits_benefit1_description_uk" id="become_supplier_benefits_benefit1_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit1_description_uk', \App\Models\Option::get('become_supplier_benefits_benefit1_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Перевага 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit2_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit2_title_uk" id="become_supplier_benefits_benefit2_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit2_title_uk', \App\Models\Option::get('become_supplier_benefits_benefit2_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit2_description_uk">Опис</label>
                                                    <textarea name="become_supplier_benefits_benefit2_description_uk" id="become_supplier_benefits_benefit2_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit2_description_uk', \App\Models\Option::get('become_supplier_benefits_benefit2_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Перевага 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit3_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit3_title_uk" id="become_supplier_benefits_benefit3_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit3_title_uk', \App\Models\Option::get('become_supplier_benefits_benefit3_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit3_description_uk">Опис</label>
                                                    <textarea name="become_supplier_benefits_benefit3_description_uk" id="become_supplier_benefits_benefit3_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit3_description_uk', \App\Models\Option::get('become_supplier_benefits_benefit3_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Перевага 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit4_title_uk">Заголовок</label>
                                                    <input type="text" name="become_supplier_benefits_benefit4_title_uk" id="become_supplier_benefits_benefit4_title_uk"
                                                           class="form-control" value="{{ old('become_supplier_benefits_benefit4_title_uk', \App\Models\Option::get('become_supplier_benefits_benefit4_title_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_benefits_benefit4_description_uk">Опис</label>
                                                    <textarea name="become_supplier_benefits_benefit4_description_uk" id="become_supplier_benefits_benefit4_description_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_benefits_benefit4_description_uk', \App\Models\Option::get('become_supplier_benefits_benefit4_description_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - Способи виведення коштів</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_payout_title_uk" id="become_supplier_payout_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_payout_title_uk', \App\Models\Option::get('become_supplier_payout_title_uk')) }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_subtitle_uk">Підзаголовок</label>
                                            <textarea name="become_supplier_payout_subtitle_uk" id="become_supplier_payout_subtitle_uk" rows="2"
                                                   class="form-control">{{ old('become_supplier_payout_subtitle_uk', \App\Models\Option::get('become_supplier_payout_subtitle_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_methods_uk">Способи виведення (кожен з нового рядка)</label>
                                            <textarea name="become_supplier_payout_methods_uk" id="become_supplier_payout_methods_uk" rows="3"
                                                   class="form-control">{{ old('become_supplier_payout_methods_uk', \App\Models\Option::get('become_supplier_payout_methods_uk')) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="become_supplier_payout_cta_uk">Текст кнопки</label>
                                            <input type="text" name="become_supplier_payout_cta_uk" id="become_supplier_payout_cta_uk"
                                                   class="form-control" value="{{ old('become_supplier_payout_cta_uk', \App\Models\Option::get('become_supplier_payout_cta_uk')) }}">
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Стати постачальником - FAQ</h5>
                                        <div class="form-group">
                                            <label for="become_supplier_faq_title_uk">Заголовок</label>
                                            <input type="text" name="become_supplier_faq_title_uk" id="become_supplier_faq_title_uk"
                                                   class="form-control" value="{{ old('become_supplier_faq_title_uk', \App\Models\Option::get('become_supplier_faq_title_uk')) }}">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Питання 1</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question1_uk">Питання</label>
                                                    <input type="text" name="become_supplier_faq_question1_uk" id="become_supplier_faq_question1_uk"
                                                           class="form-control" value="{{ old('become_supplier_faq_question1_uk', \App\Models\Option::get('become_supplier_faq_question1_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer1_uk">Відповідь</label>
                                                    <textarea name="become_supplier_faq_answer1_uk" id="become_supplier_faq_answer1_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer1_uk', \App\Models\Option::get('become_supplier_faq_answer1_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Питання 2</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question2_uk">Питання</label>
                                                    <input type="text" name="become_supplier_faq_question2_uk" id="become_supplier_faq_question2_uk"
                                                           class="form-control" value="{{ old('become_supplier_faq_question2_uk', \App\Models\Option::get('become_supplier_faq_question2_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer2_uk">Відповідь</label>
                                                    <textarea name="become_supplier_faq_answer2_uk" id="become_supplier_faq_answer2_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer2_uk', \App\Models\Option::get('become_supplier_faq_answer2_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Питання 3</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question3_uk">Питання</label>
                                                    <input type="text" name="become_supplier_faq_question3_uk" id="become_supplier_faq_question3_uk"
                                                           class="form-control" value="{{ old('become_supplier_faq_question3_uk', \App\Models\Option::get('become_supplier_faq_question3_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer3_uk">Відповідь</label>
                                                    <textarea name="become_supplier_faq_answer3_uk" id="become_supplier_faq_answer3_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer3_uk', \App\Models\Option::get('become_supplier_faq_answer3_uk')) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Питання 4</h6>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_question4_uk">Питання</label>
                                                    <input type="text" name="become_supplier_faq_question4_uk" id="become_supplier_faq_question4_uk"
                                                           class="form-control" value="{{ old('become_supplier_faq_question4_uk', \App\Models\Option::get('become_supplier_faq_question4_uk')) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label for="become_supplier_faq_answer4_uk">Відповідь</label>
                                                    <textarea name="become_supplier_faq_answer4_uk" id="become_supplier_faq_answer4_uk" rows="2"
                                                           class="form-control">{{ old('become_supplier_faq_answer4_uk', \App\Models\Option::get('become_supplier_faq_answer4_uk')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Сохранить изменения</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="content_header_menu" role="tabpanel">
                            <div class="card">
                                <div class="card-header no-border border-0 p-0">
                                    <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                        @foreach(config('langs') as $code => $flag)
                                            <li class="nav-item">
                                                <a class="nav-link {{ $code == 'ru' ? 'active' : null }}"
                                                   id="tab_{{ $code }}" data-toggle="tab" href="#tab_content_{{ $code }}" role="tab">
                                                    <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <form class="save-menu-form" method="POST" action="{{ route('admin.site-content.store') }}">
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
                        <div class="tab-pane fade" id="content_footer_menu" role="tabpanel">
                            <div class="card">
                                <div class="card-header no-border border-0 p-0">
                                    <ul class="nav nav-tabs" id="custom-tabs-one-tab-footer" role="tablist">
                                        @foreach(config('langs') as $code => $flag)
                                            <li class="nav-item">
                                                <a class="nav-link {{ $code == 'ru' ? 'active' : null }}"
                                                   id="tab_{{ $code }}_footer" data-toggle="tab" href="#tab_content_{{ $code }}_footer" role="tab">
                                                    <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <form class="save-menu-form" method="POST" action="{{ route('admin.site-content.store') }}">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
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
            let headerMenu = @json(\App\Models\Option::get('header_menu', '{}'));
            let footerMenu = @json(\App\Models\Option::get('footer_menu', '{}'));
            loadData('header', headerMenu);
            loadData('footer', footerMenu);

            function loadData(type, menu) {
                if (!menu || menu === '{}' || menu === '') {
                    return;
                }
                let menuData = typeof menu === 'string' ? JSON.parse(menu) : menu;

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

            // Handle active tab after form submission
            const activeTab = @json(old('form', session('active_tab')));
            if (activeTab) {
                let tabId, paneId;
                
                // Map form names to tab IDs
                const tabMap = {
                    'site_content': 'tab_site_content',
                    'header_menu': 'tab_header_menu',
                    'footer_menu': 'tab_footer_menu'
                };
                
                if (tabMap[activeTab]) {
                    tabId = '#' + tabMap[activeTab];
                paneId = '#content_' + activeTab;

                    // Remove active from all tabs
                    $('#site-content-tabs a.nav-link').removeClass('active');
                    $('.tab-content .tab-pane').removeClass('show active');

                    // Add active to selected tab
                $(tabId).addClass('active');
                $(paneId).addClass('show active');
                }
            }
        });
    </script>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection
