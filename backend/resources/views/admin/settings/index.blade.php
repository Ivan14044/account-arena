@extends('adminlte::page')

@section('title', 'Настройки')

@section('content_header')
<div class="content-header-modern">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 font-weight-light">
                Настройки системы
            </h1>
            <p class="text-muted mb-0 mt-1">Конфигурация и управление параметрами платформы</p>
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
            <ul class="nav nav-tabs-modern" id="settings-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab_smtp" data-toggle="pill" href="#content_smtp" role="tab">
                        <i class="fas fa-envelope mr-2"></i>SMTP
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab_cookie" data-toggle="pill" href="#content_cookie" role="tab">
                        <i class="fas fa-cookie-bite mr-2"></i>Cookie
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab_support_chat" data-toggle="pill" href="#content_support_chat" role="tab">
                        <i class="fas fa-comments mr-2"></i>Чат поддержки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab_notification_settings" data-toggle="pill"
                        href="#content_notification_settings" role="tab">
                        <i class="fas fa-bell mr-2"></i>Уведомления
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab_telegram" data-toggle="pill" href="#content_telegram" role="tab">
                        <i class="fab fa-telegram mr-2"></i>Telegram
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane" id="content_cookie" role="tabpanel">
                    <form method="POST" action="{{ route('admin.settings.store') }}">
                        @csrf
                        <input type="hidden" name="form" value="cookie">
                        <label for="">Display cookie consent for these countries</label>
                        <div class="row">
                            @foreach(config('countries') as $code => $name)
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="cookie_country_{{ $code }}"
                                            name="cookie_countries[]" value="{{ $code }}" {{ in_array($code, old('cookie_countries', json_decode(\App\Models\Option::get('cookie_countries', '[]'), true))) ? 'checked' : '' }}>
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
                <div class="tab-pane fade show active" id="content_smtp" role="tabpanel">
                    <form method="POST" action="{{ route('admin.settings.store') }}">
                        @csrf
                        <input type="hidden" name="form" value="smtp">
                        <div class="form-group">
                            <label for="smtp_from_address">From address</label>
                            <input type="email" name="smtp_from_address" id="smtp_from_address"
                                class="form-control @error('smtp_from_address') is-invalid @enderror"
                                value="{{ old('smtp_from_address', \App\Models\Option::get('smtp_from_address')) }}">
                            @error('smtp_from_address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="smtp_from_name">From name</label>
                            <input type="text" name="smtp_from_name" id="smtp_from_name"
                                class="form-control @error('smtp_from_name') is-invalid @enderror"
                                value="{{ old('smtp_from_name', \App\Models\Option::get('smtp_from_name')) }}">
                            @error('smtp_from_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="smtp_host">Host</label>
                            <input type="text" name="smtp_host" id="smtp_host"
                                class="form-control @error('smtp_host') is-invalid @enderror"
                                value="{{ old('smtp_host', \App\Models\Option::get('smtp_host')) }}">
                            @error('smtp_host')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="smtp_port">Port</label>
                            <input type="text" name="smtp_port" id="smtp_port"
                                class="form-control @error('smtp_port') is-invalid @enderror"
                                value="{{ old('smtp_port', \App\Models\Option::get('smtp_port')) }}">
                            @error('smtp_port')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="smtp_encryption">Encryption</label>
                            <select name="smtp_encryption" id="smtp_encryption"
                                class="form-control @error('smtp_encryption') is-invalid @enderror">
                                <option value="">None</option>
                                <option value="tls" {{ old('smtp_encryption', \App\Models\Option::get('smtp_encryption')) === 'tls' ? 'selected' : '' }}>TLS (usually
                                    port 587)</option>
                                <option value="ssl" {{ old('smtp_encryption', \App\Models\Option::get('smtp_encryption')) === 'ssl' ? 'selected' : '' }}>SSL (usually
                                    port 465)</option>
                            </select>
                            <small class="form-text text-muted">TLS for port 587, SSL for port 465</small>
                            @error('smtp_encryption')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="smtp_username">Username</label>
                            <input type="text" name="smtp_username" id="smtp_username"
                                class="form-control @error('smtp_username') is-invalid @enderror"
                                value="{{ old('smtp_username', \App\Models\Option::get('smtp_username')) }}"
                                placeholder="info@account-arena.com or just username">
                            <small class="form-text text-muted">Usually your full email address, but some servers require
                                just username</small>
                            @error('smtp_username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="smtp_password">Password</label>
                            <input type="password" name="smtp_password" id="smtp_password"
                                class="form-control @error('smtp_password') is-invalid @enderror"
                                value="{{ old('smtp_password', $smtpPassword) }}"
                                placeholder="Your email password or app-specific password">
                            <small class="form-text text-muted">For Gmail, use App Password (not your regular
                                password)</small>
                            @error('smtp_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Save</button>
                    </form>
                </div>

                <div class="tab-pane" id="content_support_chat" role="tabpanel">
                    <form method="POST" action="{{ route('admin.settings.store') }}">
                        @csrf
                        <input type="hidden" name="form" value="support_chat">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Информация:</strong> Включите встроенный чат поддержки для вашего сайта.
                            Пользователи смогут общаться с поддержкой прямо на сайте. Все чаты доступны в разделе "Чат
                            поддержки" в админ-панели.
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="support_chat_enabled"
                                    name="support_chat_enabled" value="1" {{ old('support_chat_enabled', \App\Models\Option::get('support_chat_enabled')) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="support_chat_enabled">
                                    Включить чат поддержки
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Когда включено, виджет чата будет отображаться в правом нижнем углу сайта.
                                Пользователи смогут создавать чаты и общаться с поддержкой в реальном времени.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="support_chat_telegram_link">
                                <i class="fab fa-telegram mr-2"></i>Ссылка на Telegram для чата поддержки
                            </label>
                            <input type="url" name="support_chat_telegram_link" id="support_chat_telegram_link"
                                class="form-control @error('support_chat_telegram_link') is-invalid @enderror"
                                value="{{ old('support_chat_telegram_link', \App\Models\Option::get('support_chat_telegram_link', 'https://t.me/support')) }}"
                                placeholder="https://t.me/your_support">
                            @error('support_chat_telegram_link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Эта ссылка будет показана пользователям при открытии чата. Они смогут выбрать - общаться в
                                окне или перейти в Telegram.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="support_chat_greeting_enabled"
                                    name="support_chat_greeting_enabled" value="1" {{ old('support_chat_greeting_enabled', \App\Models\Option::get('support_chat_greeting_enabled')) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="support_chat_greeting_enabled">
                                    Включить приветственное сообщение
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Когда включено, пользователи будут автоматически получать приветственное сообщение при
                                создании нового чата.
                            </small>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="greeting-tabs" role="tablist">
                                    @foreach(config('langs') as $code => $flag)
                                        <li class="nav-item">
                                            <a class="nav-link {{ $code == 'ru' ? 'active' : null }}"
                                                id="greeting_tab_{{ $code }}" data-toggle="pill"
                                                href="#greeting_content_{{ $code }}" role="tab">
                                                <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span>
                                                {{ strtoupper($code) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    @foreach(config('langs') as $code => $flag)
                                        <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}"
                                            id="greeting_content_{{ $code }}" role="tabpanel">
                                            <div class="form-group">
                                                <label for="support_chat_greeting_message_{{ $code }}">
                                                    <i class="fas fa-comment-dots mr-2"></i>Текст приветственного сообщения
                                                    ({{ strtoupper($code) }})
                                                </label>
                                                <textarea name="support_chat_greeting_message_{{ $code }}"
                                                    id="support_chat_greeting_message_{{ $code }}"
                                                    class="form-control @error('support_chat_greeting_message_' . $code) is-invalid @enderror"
                                                    rows="4"
                                                    placeholder="Добро пожаловать! Мы рады помочь вам. Опишите вашу проблему, и мы постараемся решить её как можно скорее.">{{ old('support_chat_greeting_message_' . $code, \App\Models\Option::get('support_chat_greeting_message_' . $code, '')) }}</textarea>
                                                @error('support_chat_greeting_message_' . $code)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted">
                                                    Это сообщение будет автоматически отправлено пользователю при создании
                                                    нового чата на языке {{ strtoupper($code) }} (если включено приветствие).
                                                </small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>


                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Готово к использованию:</strong> После включения чат будет автоматически работать.
                            Все сообщения будут сохраняться в базе данных и доступны для просмотра в админ-панели.
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Сохранить настройки</button>
                    </form>
                </div>

                <!-- Вкладка настроек уведомлений -->
                <div class="tab-pane" id="content_notification_settings" role="tabpanel">
                    <form method="POST" action="{{ route('admin.settings.store') }}">
                        <input type="hidden" name="form" value="notification_settings">
                        @csrf

                        <div class="form-group">
                            <h5 class="mb-3">
                                <i class="fas fa-bell mr-2"></i>Типы уведомлений
                            </h5>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="registration_enabled"
                                    name="registration_enabled" value="1" {{ $notificationSettings->registration_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="registration_enabled">
                                    <strong>Новые регистрации</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о регистрации новых пользователей</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="product_purchase_enabled"
                                    name="product_purchase_enabled" value="1" {{ $notificationSettings->product_purchase_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="product_purchase_enabled">
                                    <strong>Покупки товаров</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о новых покупках товаров</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="dispute_created_enabled"
                                    name="dispute_created_enabled" value="1" {{ $notificationSettings->dispute_created_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="dispute_created_enabled">
                                    <strong>Новые претензии</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о создании новых претензий на товары</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="payment_enabled" name="payment_enabled"
                                    value="1" {{ $notificationSettings->payment_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="payment_enabled">
                                    <strong>Платежи</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о платежах и транзакциях</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="topup_enabled" name="topup_enabled"
                                    value="1" {{ $notificationSettings->topup_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="topup_enabled">
                                    <strong>Пополнения баланса</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о пополнениях баланса пользователей</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="support_chat_enabled"
                                    name="support_chat_enabled" value="1" {{ $notificationSettings->support_chat_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="support_chat_enabled">
                                    <strong>Сообщения в чате поддержки</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о новых сообщениях от пользователей в чате
                                        поддержки</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="manual_delivery_enabled"
                                    name="manual_delivery_enabled" value="1" {{ ($notificationSettings->manual_delivery_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="manual_delivery_enabled">
                                    <strong>Ручная обработка заказов</strong>
                                    <br>
                                    <small class="text-muted">Уведомления о новых заказах, требующих ручной обработки</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <h5 class="mb-3">
                                <i class="fas fa-volume-up mr-2"></i>Звуковое оповещение
                            </h5>

                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="sound_enabled" name="sound_enabled"
                                    value="1" {{ $notificationSettings->sound_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="sound_enabled">
                                    <strong>Включить звуковое оповещение</strong>
                                    <br>
                                    <small class="text-muted">Воспроизводить звук при получении новых уведомлений</small>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Сохранить настройки
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Telegram Settings -->
                <div class="tab-pane fade" id="content_telegram" role="tabpanel">
                    <form method="POST" action="{{ route('admin.settings.store') }}">
                        <input type="hidden" name="form" value="telegram">
                        @csrf

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Информация:</strong> Настройте интеграцию с Telegram ботом для получения сообщений от
                            клиентов.
                            Создайте бота через <a href="https://t.me/BotFather" target="_blank"
                                class="alert-link">@BotFather</a> и получите токен бота.
                        </div>

                        <div class="form-group">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="telegram_client_enabled"
                                    name="telegram_client_enabled" value="1" {{ old('telegram_client_enabled', $telegramSettings['enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="telegram_client_enabled">
                                    <strong>Включить Telegram поддержку</strong>
                                    <br>
                                    <small class="text-muted">Включить получение сообщений из Telegram через бота</small>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="telegram_bot_token">Токен бота</label>
                            <input type="text" class="form-control @error('telegram_bot_token') is-invalid @enderror"
                                id="telegram_bot_token" name="telegram_bot_token"
                                value="{{ old('telegram_bot_token', $telegramSettings['bot_token']) }}"
                                placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz">
                            @error('telegram_bot_token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($telegramSettings['bot_username'])
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-2"></i>
                                <strong>Бот настроен:</strong> {{ '@' . $telegramSettings['bot_username'] }}
                                <br>
                                <small>ID бота: {{ $telegramSettings['bot_id'] ?? 'N/A' }}</small>
                            </div>
                        @endif

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Сохранить настройки
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection