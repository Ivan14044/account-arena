<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    {{-- SEO Meta Tags --}}
    <title>{{ $pageTitle ?? 'Account Arena' }}</title>
    <meta name="description" content="{{ Str::limit($metaDescription ?? '', 160) }}">
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $pageTitle ?? 'Account Arena' }}">
    <meta property="og:description" content="{{ Str::limit($metaDescription ?? '', 160) }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if(isset($ogImage) && $ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $pageTitle ?? 'Account Arena' }}">
    @endif
    <meta property="og:site_name" content="Account Arena">
    <meta property="og:locale" content="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}">
    
    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle ?? 'Account Arena' }}">
    <meta name="twitter:description" content="{{ Str::limit($metaDescription ?? '', 160) }}">
    @if(isset($ogImage) && $ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    
    {{-- Article meta tags --}}
    @if(isset($article) && $article)
        @if(isset($article->created_at))
        <meta property="article:published_time" content="{{ $article->created_at->toIso8601String() }}">
        @endif
        @if(isset($article->updated_at))
        <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}">
        @endif
        @if(isset($article->author))
        <meta property="article:author" content="{{ $article->author }}">
        @endif
        @if(isset($article->categories) && $article->categories->count() > 0)
            @foreach($article->categories as $category)
            <meta property="article:section" content="{{ $category->translate('name', $locale ?? app()->getLocale()) }}">
            @endforeach
        @endif
    @endif
    
    {{-- Product meta tags --}}
    @if(isset($product) && $product)
        <meta property="product:availability" content="{{ $product->getAvailableStock() > 0 ? 'in stock' : 'out of stock' }}">
        @if(isset($product->price))
        <meta property="product:price:amount" content="{{ $product->price }}">
        <meta property="product:price:currency" content="{{ config('app.currency', 'USD') }}">
        @endif
        @if(isset($product->category))
        <meta property="product:category" content="{{ $product->category->translate('name', $locale ?? app()->getLocale()) }}">
        @endif
    @endif
    
    {{-- Hreflang для мультиязычности --}}
    @if(isset($alternateUrls) && is_array($alternateUrls))
        @foreach($alternateUrls as $lang => $url)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $url }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">
    @endif
    
    {{-- Canonical URL (единый стандарт: без слэша в конце) --}}
    <link rel="canonical" href="{{ rtrim(url()->current(), '/') }}">
    
    {{-- Alternate link to SPA version (for users) --}}
    @if(isset($spaUrl) && $spaUrl)
    <link rel="alternate" href="{{ $spaUrl }}" type="text/html">
    @endif
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    {{-- Preconnect для внешних ресурсов --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    
    {{-- Preload CSS --}}
    @if(file_exists(public_path('css/app.css')))
    <link rel="preload" href="{{ asset('css/app.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    
    {{-- Структурированные данные (Schema.org JSON-LD) --}}
    @stack('structured-data')
    
    {{-- Additional head content --}}
    @stack('head')
</head>
<body>
    <div id="app">
        {{-- SEO контент сразу в HTML --}}
        <main class="seo-content-wrapper">
            @yield('content')
        </main>
        
        {{-- Vue.js для интерактивности (гидратация) --}}
        <div id="vue-app-mount"></div>
    </div>
    
    {{-- Vue.js для гидратации --}}
    @if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}" defer></script>
    @endif
    
    @stack('scripts')
</body>
</html>
