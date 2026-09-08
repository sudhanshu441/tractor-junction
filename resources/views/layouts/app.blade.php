@extends('layouts.base')

@push('head')
    @include('partials.analytics')
@endpush

@section('body')
    @include('partials.header')
    <main>@yield('content')</main>
    @include('partials.footer')
@endsection

@push('schema')
    @include('partials.consent-banner')
@endpush
