@extends('seo.layout')

@section('content')
<article class="container mx-auto px-4 py-8">
    {{-- H1 заголовок (уникальный, не дублирует title) --}}
    <h1 class="text-4xl font-bold mb-6">{{ $title }}</h1>
    
    {{-- Дата публикации --}}
    @if($article->created_at)
    <div class="text-gray-500 mb-4">
        {{ $article->created_at->format('d.m.Y') }}
    </div>
    @endif
    
    {{-- Категории --}}
    @if($article->categories && $article->categories->count() > 0)
    <div class="mb-4">
        @foreach($article->categories as $category)
            <span class="inline-block bg-gray-200 px-3 py-1 rounded mr-2 mb-2">
                {{ $category->translate('name', $locale) }}
            </span>
        @endforeach
    </div>
    @endif
    
    {{-- Изображение статьи --}}
    @if($article->img)
    <div class="mb-6">
        <img src="{{ \Illuminate\Support\Facades\Storage::url($article->img) }}" alt="{{ $title }}" class="w-full rounded-lg">
    </div>
    @endif
    
    {{-- SEO-текст (300-500 слов) --}}
    @if($seoText)
    <div class="seo-text mb-8 prose prose-lg max-w-none">
        {!! $seoText !!}
    </div>
    @endif
</article>
@endsection
