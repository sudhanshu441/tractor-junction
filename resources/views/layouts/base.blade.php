<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $seo['title'] ?? config('kj.brand.name'))</title>
    <meta name="description" content="@yield('meta_description', $seo['description'] ?? \App\Models\Setting::get('seo.meta_description', ''))">
    @isset($seo)
        @if ($seo['keywords'])<meta name="keywords" content="{{ $seo['keywords'] }}">@endif
        <meta name="robots" content="{{ $seo['robots'] }}">
        <link rel="canonical" href="{{ $seo['canonical'] }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', config('kj.brand.name')) }}">
        <meta property="og:title" content="{{ $seo['og_title'] }}">
        <meta property="og:description" content="{{ $seo['og_description'] }}">
        <meta property="og:url" content="{{ $seo['canonical'] }}">
        <meta property="og:image" content="{{ $seo['og_image'] }}">
        <meta name="twitter:card" content="summary_large_image">
    @endisset

    {{-- Both locales are served from the same content, so every page declares both. --}}
    @foreach (\App\Support\Locale::alternates() as $code => $href)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ \App\Support\Locale::alternates()['en'] ?? url()->current() }}">

    <link rel="icon" href="{{ asset('assets/brand/favicon.svg') }}" type="image/svg+xml">
    {{-- Fonts are self-hosted: a CDN round-trip is the slowest thing on a 3G handset. --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ config('app.asset_version', '1') }}">
    @stack('styles')
    @stack('head')
</head>
<body class="@yield('body_class')">
    @yield('body')

    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}?v={{ config('app.asset_version', '1') }}"></script>
    @stack('scripts')

    @stack('schema')

    @if (session('success')) <script>$(function () { KJ.toast(@json(session('success')), 'success'); });</script> @endif
    @if (session('error'))   <script>$(function () { KJ.toast(@json(session('error')), 'danger'); });</script> @endif
</body>
</html>
