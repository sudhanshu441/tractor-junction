<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('kj.brand.name'))</title>
    <meta name="description" content="@yield('meta_description', \App\Models\Setting::get('seo.meta_description', ''))">

    <link rel="icon" href="{{ asset('assets/brand/favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ config('app.asset_version', '1') }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">
    @yield('body')

    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}?v={{ config('app.asset_version', '1') }}"></script>
    @stack('scripts')

    @if (session('success')) <script>$(function () { KJ.toast(@json(session('success')), 'success'); });</script> @endif
    @if (session('error'))   <script>$(function () { KJ.toast(@json(session('error')), 'danger'); });</script> @endif
</body>
</html>
