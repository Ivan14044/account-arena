@extends('adminlte::page')

@section('title', 'Рекламные баннеры')

@section('content_header')
    <h1>Управление рекламными баннерами</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Фильтры</h3>
            <div class="card-tools">
                <a href="{{ route('admin.banners.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Создать баннер
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Информация о позициях баннеров:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Широкий баннер (home_top_wide):</strong> 1 позиция - отображается выше обычных баннеров и занимает всю ширину</li>
                    <li><strong>Обычные баннеры (home_top):</strong> 4 позиции - отображаются в ряд под широким баннером. Баннеры с порядком 1-4 заменят плейсхолдеры "Здесь реклама 1-4" соответственно</li>
                </ul>
            </div>
            
            <form action="{{ route('admin.banners.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="is_active">Статус</label>
                            <select name="is_active" id="is_active" class="form-control">
                                <option value="">Все</option>
                                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Активен</option>
                                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Найти
                                </button>
                                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary btn-sm mt-2">
                                    <i class="fas fa-redo"></i>Сбросить</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Список баннеров ({{ $banners->total() }} всего)</h3>
        </div>
        <div class="card-body p-0">
            @if($banners->count() > 0)
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px">ID</th>
                        <th style="width: 150px">Изображение</th>
                        <th>Название</th>
                        <th style="width: 150px">Тип</th>
                        <th style="width: 120px">Позиция</th>
                        <th></th>
                        <th style="width: 100px">Статус</th>
                        <th style="width: 150px">Период показа</th>
                        <th style="width: 150px">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($banners as $banner)
                    <tr>
                        <td>{{ $banner->id }}</td>
                        <td>
                            @if($banner->image_url)
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" 
                                     class="img-thumbnail" style="max-width: 120px; max-height: 60px;">
                            @else
                                <span class="text-muted">Нет изображения</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $banner->title }}</strong>
                            @if($banner->title_en || $banner->title_uk)
                                <br>
                                <small class="text-muted">
                                    @if($banner->title_en)
                                        🇬🇧 {{ $banner->title_en }}
                                    @endif
                                    @if($banner->title_uk)
                                        <br>🇺🇦 {{ $banner->title_uk }}
                                    @endif
                                </small>
                            @endif
                        </td>
                        <td>
                            @if($banner->position === 'home_top_wide')
                                <span class="badge badge-info">Широкий баннер</span>
                            @else
                                <span class="badge badge-secondary">Обычный</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-primary">
                                @if($banner->position === 'home_top_wide')
                                    Позиция: {{ $banner->order }}
                                @else
                                    Баннер {{ $banner->order }}
                                @endif
                            </span>
                        </td>
                        <td>
                            @if($banner->link)
                                <a href="{{ $banner->link }}" target="_blank" class="text-primary">
                                    <i class="fas fa-external-link-alt"></i> Ссылка
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($banner->isCurrentlyActive())
                                <span class="badge badge-success">Активен</span>
                            @elseif($banner->is_active)
                                <span class="badge badge-warning"></span>
                            @else
                                <span class="badge badge-secondary"></span>
                            @endif
                        </td>
                        <td>
                            @if($banner->start_date || $banner->end_date)
                                <small>
                                    @if($banner->start_date)
                                        <strong>С:</strong> {{ $banner->start_date->format('d.m.Y') }}<br>
                                    @endif
                                    @if($banner->end_date)
                                        <strong>До:</strong> {{ $banner->end_date->format('d.m.Y') }}
                                    @endif
                                </small>
                            @else
                                <span class="text-muted">Без ограничений</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" 
                                  style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Вы уверены, что хотите удалить этот баннер?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-3 text-center text-muted">
                <p>Баннеры не найдены</p>
            </div>
            @endif
        </div>
        @if($banners->hasPages())
        <div class="card-footer">
            {{ $banners->links() }}
        </div>
        @endif
    </div>
@endsection

