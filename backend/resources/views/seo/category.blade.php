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
    <h1 class="text-4xl font-bold mb-6">{{ $name ?? $category->translate('name') }}</h1>
    
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
    
    {{-- Список товаров/статей категории --}}
    @if($items && $items->count() > 0)
    <div class="category-items mt-8">
        <h2 class="text-2xl font-semibold mb-4">
            @if($category->type === \App\Models\Category::TYPE_PRODUCT)
                Товары в категории
            @else
                Статьи в категории
            @endif
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($items as $item)
                @if($category->type === \App\Models\Category::TYPE_PRODUCT)
                    <div class="product-card border rounded-lg p-4">
                        <h3 class="text-xl font-semibold mb-2">
                            {{ $item->title }}
                        </h3>
                        @if($item->description)
                        <p class="text-gray-600 mb-2">
                            {{ Str::limit(strip_tags($item->description), 150) }}
                        </p>
                        @endif
                        <p class="text-lg font-bold text-green-600">
                            ${{ number_format($item->price, 2) }}
                        </p>
                    </div>
                @else
                    <div class="article-card border rounded-lg p-4">
                        <h3 class="text-xl font-semibold mb-2">
                            {{ $item->translate('title', $locale) }}
                        </h3>
                        @if($item->translate('short', $locale))
                        <p class="text-gray-600">
                            {{ Str::limit(strip_tags($item->translate('short', $locale)), 150) }}
                        </p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif
</article>
@endsection
