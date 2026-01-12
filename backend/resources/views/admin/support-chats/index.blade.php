@extends('adminlte::page')

@section('title', 'Чат поддержки')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    <i class="fas fa-comments mr-2 text-primary"></i>Чат поддержки
                </h1>
                <p class="text-muted mb-0 mt-1">Управление чатами поддержки и общение с пользователями</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn {{ !request()->has('source') || request('source') === '' ? 'btn-primary' : 'btn-outline-primary' }} btn-modern btn-sm px-3" 
                   href="{{ route('admin.support-chats.index') }}">
                    <i class="fas fa-list mr-1"></i> Все
                </a>
                <a class="btn {{ request('source') === 'website' ? 'btn-primary' : 'btn-outline-primary' }} btn-modern btn-sm px-3" 
                   href="{{ route('admin.support-chats.index', ['source' => 'website']) }}">
                    <i class="fas fa-globe mr-1"></i> Сайт
                </a>
                <a class="btn {{ request('source') === 'telegram' ? 'btn-primary' : 'btn-outline-primary' }} btn-modern btn-sm px-3" 
                   href="{{ route('admin.support-chats.index', ['source' => 'telegram']) }}">
                    <i class="fab fa-telegram mr-1"></i> Telegram
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

    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0">Список активных диалогов</h5>
        </div>
        <div class="card-body-modern p-0">
            <div class="table-responsive">
                <table class="table table-hover modern-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th>Пользователь</th>
                            <th>Email / Контакт</th>
                            <th class="text-center">Источник</th>
                            <th class="text-center">Статус</th>
                            <th class="text-center">Назначен</th>
                            <th class="text-center">Последняя активность</th>
                            <th class="text-center">Оценка</th>
                            <th style="width: 120px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chats as $chat)
                            <tr @if(($chat->unread_count ?? 0) > 0) class="row-unread" @endif>
                                <td class="text-center align-middle">
                                    <span class="badge badge-light font-weight-bold">#{{ $chat->id }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle-sm mr-2 {{ $chat->isFromTelegram() ? 'bg-info' : 'bg-secondary' }}">
                                            {{ mb_substr($chat->user->name ?? $chat->guest_name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark">
                                                {{ $chat->isFromTelegram() ? ($chat->guest_name ?? 'Telegram User') : ($chat->user->name ?? $chat->guest_name ?? 'Гость') }}
                                            </div>
                                            @if(($chat->unread_count ?? 0) > 0)
                                                <span class="badge badge-danger badge-pill unread-badge-modern">
                                                    {{ $chat->unread_count > 99 ? '99+' : $chat->unread_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle text-muted small">
                                    @if($chat->isFromTelegram())
                                        <span class="text-info"><i class="fab fa-telegram-plane mr-1"></i>Telegram API</span>
                                    @else
                                        {{ $chat->user ? $chat->user->email : ($chat->guest_email ?? '—') }}
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($chat->isFromTelegram())
                                        <span class="badge badge-info badge-modern px-2 py-1">
                                            <i class="fab fa-telegram mr-1"></i> Telegram
                                        </span>
                                    @else
                                        <span class="badge badge-secondary badge-modern px-2 py-1">
                                            <i class="fas fa-globe mr-1"></i> Сайт
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @php
                                        $statusClass = [
                                            'open' => 'success',
                                            'closed' => 'secondary',
                                            'waiting' => 'warning'
                                        ][$chat->status] ?? 'info';
                                        $statusText = [
                                            'open' => 'Открыт',
                                            'closed' => 'Закрыт',
                                            'waiting' => 'Ожидание'
                                        ][$chat->status] ?? $chat->status;
                                    @endphp
                                    <span class="badge badge-{{ $statusClass }} badge-modern">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="text-center align-middle small">
                                    @if($chat->assignedAdmin)
                                        <span class="text-dark font-weight-500">{{ $chat->assignedAdmin->name }}</span>
                                    @else
                                        <span class="text-muted italic">Свободный чат</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle text-muted small" data-order="{{ $chat->last_message_at ? strtotime($chat->last_message_at) : 0 }}">
                                    @if($chat->last_message_at)
                                        <i class="far fa-clock mr-1"></i> {{ $chat->last_message_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($chat->rating)
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="text-warning mb-1" style="font-size: 0.8rem;">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star {{ $i <= $chat->rating ? '' : 'text-muted opacity-25' }}"></i>
                                                @endfor
                                            </div>
                                            @if($chat->rating_comment)
                                                <i class="fas fa-comment-dots text-info" title="{{ $chat->rating_comment }}" data-toggle="tooltip"></i>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <div class="action-buttons justify-content-center">
                                        <a href="{{ route('admin.support-chats.show', $chat->id) }}" 
                                           class="btn btn-sm btn-primary px-3"
                                           title="Перейти к диалогу"
                                           data-toggle="tooltip">
                                            <i class="fas fa-reply mr-1"></i> Ответить
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-comments fa-3x mb-3 opacity-20"></i>
                                    <p class="mb-0 font-weight-bold">Активные чаты не найдены</p>
                                    <small>Все диалоги завершены или еще не начаты</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($chats->hasPages())
            <div class="card-footer-modern bg-white p-3 border-top">
                <div class="d-flex justify-content-center">
                    {{ $chats->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .row-unread {
            background-color: rgba(78, 115, 223, 0.05);
        }
        .row-unread:hover {
            background-color: rgba(78, 115, 223, 0.08) !important;
        }
        .unread-badge-modern {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
            position: relative;
            top: -2px;
        }
        .avatar-circle-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gap-2 { gap: 0.5rem; }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
