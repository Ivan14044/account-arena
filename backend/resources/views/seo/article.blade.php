@extends('seo.layout')

@push('structured-data')
@if(isset($structuredData))
<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
@endpush

@section('content')
<article class="container mx-auto px-4 py-8">
    {{-- H1 заголовок (уникальный, не дублирует title) --}}
    <h1 class="text-4xl font-bold mb-6">{{ $title }}</h1>
    
    {{-- Дата публикации --}}
    @if($article->created_at)
    <div class="text-gray-500 mb-4">
        <time datetime="{{ $article->created_at->toIso8601String() }}">
            {{ $article->created_at->format('d.m.Y') }}
        </time>
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
        @php
            $imgPath = $article->img;
            // Убираем двойной /storage/ если он есть
            $imgPath = preg_replace('#/storage//storage/#', '/storage/', $imgPath);
            $imgPath = preg_replace('#^storage//storage/#', 'storage/', $imgPath);
            
            // Если путь уже содержит storage/, используем его напрямую
            if (str_starts_with(ltrim($imgPath, '/'), 'storage/')) {
                $imageUrl = url('/' . ltrim($imgPath, '/'));
            } else {
                $imageUrl = \Illuminate\Support\Facades\Storage::url($imgPath);
                // Дополнительная проверка на двойной /storage/ после Storage::url
                $imageUrl = preg_replace('#/storage//storage/#', '/storage/', $imageUrl);
            }
        @endphp
        <img src="{{ $imageUrl }}" 
             alt="{{ $title }}" 
             class="w-full rounded-lg"
             loading="lazy"
             width="1200"
             height="630">
    </div>
    @endif
    
    {{-- SEO-текст (300-500 слов) --}}
    @if($seoText)
    <div class="seo-text mb-8 prose prose-lg max-w-none">
        {!! $seoText !!}
    </div>
    @endif
    
    {{-- Внутренняя перелинковка: Связанные товары --}}
    @if($article->categories && $article->categories->count() > 0)
        @php
            $categoryIds = $article->categories->pluck('id')->toArray();
            $relatedProducts = \App\Models\ServiceAccount::whereIn('category_id', $categoryIds)
                ->where('is_active', true)
                ->limit(5)
                ->get();
        @endphp
        @if($relatedProducts->count() > 0)
        <div class="related-products mt-12 pt-8 border-t border-gray-200">
            <h2 class="text-2xl font-semibold mb-4">Рекомендуемые товары</h2>
            <ul class="space-y-2">
                @foreach($relatedProducts as $product)
                    <li>
                        <a href="{{ url('/seo/products/' . $product->id) }}" 
                           class="text-blue-600 hover:text-blue-800 hover:underline">
                            @php
                                $productTitle = $locale === 'uk' ? ($product->title_uk ?: $product->title) : 
                                               ($locale === 'en' ? ($product->title_en ?: $product->title) : $product->title);
                            @endphp
                            {{ $productTitle }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
        
        {{-- Связанные категории --}}
        <div class="related-categories mt-8 pt-8 border-t border-gray-200">
            <h2 class="text-2xl font-semibold mb-4">Категории</h2>
            <ul class="space-y-2">
                @foreach($article->categories as $category)
                    <li>
                        <a href="{{ url('/seo/categories/' . $category->id) }}" 
                           class="text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $category->translate('name', $locale) ?: $category->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</article>
@endsection
