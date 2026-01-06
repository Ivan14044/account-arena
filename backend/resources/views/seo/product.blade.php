@extends('seo.layout')

@section('content')
<article class="container mx-auto px-4 py-8">
    {{-- H1 заголовок (уникальный, не дублирует title) --}}
    <h1 class="text-4xl font-bold mb-6">{{ $title }}</h1>
    
    {{-- Изображение товара --}}
    @if($product->image_url)
    <div class="mb-6">
        <img src="{{ $product->image_url }}" alt="{{ $title }}" class="w-full max-w-md rounded-lg">
    </div>
    @endif
    
    {{-- Цена --}}
    @if($product->price)
    <div class="text-3xl font-bold text-green-600 mb-4">
        ${{ number_format($product->price, 2) }}
    </div>
    @endif
    
    {{-- Описание --}}
    @if($description)
    <div class="description mb-6 prose prose-lg max-w-none">
        {!! nl2br(e($description)) !!}
    </div>
    @endif
    
    {{-- SEO-текст (300-500 слов) --}}
    @if($seoText)
    <div class="seo-text mb-8 prose prose-lg max-w-none">
        {!! nl2br(e($seoText)) !!}
    </div>
    @endif
    
    {{-- Инструкция по использованию (если есть) --}}
    @if($instruction)
    <div class="instruction mb-8">
        <h2 class="text-2xl font-semibold mb-4">Инструкция по использованию</h2>
        <div class="prose prose-lg max-w-none">
            {!! nl2br(e($instruction)) !!}
        </div>
    </div>
    @endif
    
    {{-- Кнопка покупки (для Vue.js гидратации) --}}
    <div id="product-buy-button" data-product-id="{{ $product->id }}"></div>
</article>
@endsection
