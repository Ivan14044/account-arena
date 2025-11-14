@extends('adminlte::page')

@section('title', 'Статистика чата поддержки')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    <i class="fas fa-chart-bar mr-2"></i>Статистика чата поддержки
                </h1>
            </div>
            <div>
                <a href="{{ route('admin.support-chats.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class="card card-modern">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $totalChats }}</h3>
                    <p class="text-muted mb-0">Всего чатов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-modern">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-success">{{ $openChats }}</h3>
                    <p class="text-muted mb-0">Открытых</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-modern">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-warning">{{ $pendingChats }}</h3>
                    <p class="text-muted mb-0">В ожидании</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-modern">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-secondary">{{ $closedChats }}</h3>
                    <p class="text-muted mb-0">Закрытых</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <h5 class="mb-0">Чаты по периодам</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Сегодня:</strong> {{ $todayChats }}
                    </div>
                    <div class="mb-3">
                        <strong>За неделю:</strong> {{ $weekChats }}
                    </div>
                    <div>
                        <strong>За месяц:</strong> {{ $monthChats }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <h5 class="mb-0">Показатели качества</h5>
                </div>
                <div class="card-body">
                    @if($averageResponseTime && $averageResponseTime->avg_time)
                        <div class="mb-3">
                            <strong>Среднее время ответа:</strong><br>
                            {{ round($averageResponseTime->avg_time) }} мин.
                        </div>
                    @endif
                    @if($averageRating)
                        <div>
                            <strong>Средняя оценка:</strong><br>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round($averageRating) ? 'text-warning' : 'text-muted' }}"></i>
                            @endfor
                            <span class="ml-2">{{ number_format($averageRating, 1) }}/5</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
