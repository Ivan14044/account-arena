@extends('adminlte::page')

@section('title', 'Уведомления')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Уведомления</h1>
        <div class="d-flex flex-column flex-sm-row w-100 w-md-auto">
            @if($notifications->where('is_read', false)->count() > 0)
            <form action="{{ route('supplier.notifications.mark-all-read') }}" method="POST" class="d-inline mb-2 mb-sm-0 mr-sm-2">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-double"></i> Отметить все как прочитанные
                </button>
            </form>
            @endif
            <a href="{{ route('supplier.dashboard') }}" class="btn btn-secondary mb-2 mb-sm-0">
                <i class="fas fa-home"></i> Панель
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Все уведомления</h3>
        </div>
        <div class="card-body p-0">
            @if($notifications->count() > 0)
            <ul class="list-group list-group-flush">
                @foreach($notifications as $notification)
                <li class="list-group-item {{ !$notification->is_read ? 'bg-light' : '' }}">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-start">
                        <div class="flex-grow-1 mb-2 mb-sm-0">
                            <div class="d-flex align-items-center mb-1 flex-wrap">
                                @if($notification->type === 'sale')
                                    <i class="fas fa-shopping-cart text-success mr-2"></i>
                                @elseif($notification->type === 'low_stock')
                                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                                @elseif($notification->type === 'withdrawal')
                                    <i class="fas fa-money-check-alt text-info mr-2"></i>
                                @else
                                    <i class="fas fa-bell text-primary mr-2"></i>
                                @endif
                                <strong>{{ $notification->title }}</strong>
                                @if(!$notification->is_read)
                                    <span class="badge badge-primary ml-2">Новое</span>
                                @endif
                            </div>
                            <p class="mb-1">{{ $notification->message }}</p>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> {{ $notification->created_at->diffForHumans() }}
                                ({{ $notification->created_at->format('d.m.Y H:i') }})
                            </small>
                        </div>
                        <div class="ml-sm-3">
                            @if(!$notification->is_read)
                            <form action="{{ route('supplier.notifications.mark-read', $notification) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Отметить как прочитанное">
                                    <i class="fas fa-check"></i> <span class="d-sm-none">Прочитано</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
            @else
            <div class="p-4 text-center text-muted">
                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                <p>У вас пока нет уведомлений</p>
            </div>
            @endif
        </div>
        @if($notifications->hasPages())
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
@endsection

