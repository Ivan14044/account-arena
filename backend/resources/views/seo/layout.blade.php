<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    {{-- SEO Meta Tags --}}
    <title>{{ $pageTitle ?? config('app.name') }}</title>
    <meta name="description" content="{{ Str::limit($metaDescription ?? '', 160) }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $pageTitle ?? config('app.name') }}">
    <meta property="og:description" content="{{ Str::limit($metaDescription ?? '', 160) }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    {{-- Preload CSS --}}
    @if(file_exists(public_path('css/app.css')))
    <link rel="preload" href="{{ asset('css/app.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    
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
