@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/ {{ $page->title }}</span>
    </nav>

    <div class="row">
        <div class="col-lg-9">
            <h1 class="h4 mb-3">{{ $page->title }}</h1>
            <div class="kj-prose">{!! $page->content !!}</div>
        </div>
    </div>
</div>
@endsection

@push('schema')
@php
    $breadcrumbSchema = app(\App\Domain\Seo\Services\JsonLd::class)->breadcrumbs([
        ['name' => __('Home'), 'url' => route('home')],
        ['name' => $page->title, 'url' => null],
    ]);
@endphp
<script type="application/ld+json">@json($breadcrumbSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)</script>
@endpush
