@extends('seo.layout')

@push('structured-data')
@if(isset($structuredData))
<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- H1 заголовок --}}
    <h1 class="text-4xl font-bold mb-6">Статьи</h1>
    
    {{-- SEO-текст (если нужен) --}}
    @if($metaDescription)
    <div class="seo-text mb-8 prose prose-lg max-w-none">
        <p>{{ $metaDescription }}</p>
    </div>
    @endif
    
    {{-- Список статей --}}
    @if($articles && $articles->count() > 0)
    <div class="articles-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($articles as $article)
            <article class="article-card border rounded-lg p-4 hover:shadow-lg transition">
                @if($article->img)
                <div class="mb-4">
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
                         alt="{{ $article->translate('title', $locale) }}" 
                         class="w-full rounded-lg"
                         loading="lazy"
                         width="400"
                         height="300">
                </div>
                @endif
                
                <h2 class="text-xl font-semibold mb-2">
                    <a href="{{ route('seo.article', $article->id) }}" class="hover:text-blue-600">
                        {{ $article->translate('title', $locale) }}
                    </a>
                </h2>
                
                @if($article->translate('short', $locale))
                <p class="text-gray-600 mb-2">
                    {{ Str::limit(strip_tags($article->translate('short', $locale)), 150) }}
                </p>
                @endif
                
                <div class="text-sm text-gray-500">
                    {{ $article->created_at->format('d.m.Y') }}
                </div>
            </article>
        @endforeach
    </div>
    
    {{-- Пагинация --}}
    @if($articles->hasPages())
    <div class="mt-8">
        {{ $articles->links() }}
    </div>
    @endif
    @else
    <p class="text-gray-600">Статьи не найдены.</p>
    @endif
</div>
@endsection
