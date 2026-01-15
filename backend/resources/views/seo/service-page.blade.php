@extends('seo.layout')

@push('structured-data')
@if(isset($structuredData))
<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
@endpush

@section('content')
<article class="container mx-auto px-4 py-8 max-w-4xl">
    {{-- H1 заголовок --}}
    <h1 class="text-4xl font-bold mb-6 text-gray-900">{{ $title }}</h1>
    
    {{-- SEO-контент --}}
    @if($content)
    <div class="seo-content prose prose-lg max-w-none mb-8">
        {!! $content !!}
    </div>
    @else
    <div class="seo-content prose prose-lg max-w-none mb-8">
        <p class="text-gray-600">{{ $metaDescription }}</p>
    </div>
    @endif
    
    {{-- Ссылка на SPA версию для пользователей --}}
    @if(isset($spaUrl) && $spaUrl)
    <div class="mt-8 pt-8 border-t border-gray-200">
        <p class="text-sm text-gray-500 mb-2">
            {{ __('Для интерактивной версии страницы перейдите по ссылке:', [], $locale) }}
        </p>
        <a href="{{ $spaUrl }}" 
           class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
            {{ __('Открыть интерактивную версию', [], $locale) }}
        </a>
    </div>
    @endif
</article>
@endsection
