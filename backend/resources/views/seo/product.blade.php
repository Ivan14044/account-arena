@extends('seo.layout')

@push('structured-data')
@if(isset($structuredData))
<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
@if(isset($breadcrumbs) && count($breadcrumbs) > 0)
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        @foreach($breadcrumbs as $index => $crumb)
        {
            "@type": "ListItem",
            "position": {{ $index + 1 }},
            "name": "{{ $crumb['name'] }}",
            "item": "{{ $crumb['url'] }}"
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endif
@endpush

@section('content')
<article class="container mx-auto px-4 py-8">
    {{-- Breadcrumbs --}}
    @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center text-sm text-gray-600">
            @foreach($breadcrumbs as $index => $crumb)
                <li class="flex items-center">
                    @if($index > 0)
                        <span class="mx-2">/</span>
                    @endif
                    @if($loop->last)
                        <span class="text-gray-900 font-semibold">{{ $crumb['name'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="hover:text-blue-600">{{ $crumb['name'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    @endif
    
    {{-- H1 заголовок (уникальный, не дублирует title) --}}
    <h1 class="text-4xl font-bold mb-6">{{ $title }}</h1>
    
    {{-- Изображение товара --}}
    @if($product->image_url)
    <div class="mb-6">
        <img src="{{ $product->image_url }}" 
             alt="{{ $title }}" 
             class="w-full max-w-md rounded-lg"
             loading="lazy"
             width="800"
             height="600">
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
    
    {{-- Внутренняя перелинковка: Похожие товары --}}
    @if($product->category)
        @php
            $similarProducts = \App\Models\ServiceAccount::where('category_id', $product->category_id)
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->limit(5)
                ->get();
        @endphp
        @if($similarProducts->count() > 0)
        <div class="similar-products mt-12 pt-8 border-t border-gray-200">
            <h2 class="text-2xl font-semibold mb-4">Похожие товары</h2>
            <ul class="space-y-2">
                @foreach($similarProducts as $similar)
                    <li>
                        <a href="{{ url('/seo/products/' . $similar->id) }}" 
                           class="text-blue-600 hover:text-blue-800 hover:underline">
                            @php
                                $similarTitle = $locale === 'uk' ? ($similar->title_uk ?: $similar->title) : 
                                               ($locale === 'en' ? ($similar->title_en ?: $similar->title) : $similar->title);
                            @endphp
                            {{ $similarTitle }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
        
        {{-- Связанные статьи из категории --}}
        @php
            $relatedArticles = \App\Models\Article::whereHas('categories', function($q) use ($product) {
                $q->where('categories.id', $product->category_id);
            })
            ->where('status', 'published')
            ->limit(3)
            ->get();
        @endphp
        @if($relatedArticles->count() > 0)
        <div class="related-articles mt-8 pt-8 border-t border-gray-200">
            <h2 class="text-2xl font-semibold mb-4">Полезные статьи</h2>
            <ul class="space-y-2">
                @foreach($relatedArticles as $article)
                    <li>
                        <a href="{{ url('/seo/articles/' . $article->id) }}" 
                           class="text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $article->translate('title', $locale) ?: $article->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    @endif
    
    {{-- Кнопка покупки (для Vue.js гидратации) --}}
    <div id="product-buy-button" data-product-id="{{ $product->id }}"></div>
</article>
@endsection
