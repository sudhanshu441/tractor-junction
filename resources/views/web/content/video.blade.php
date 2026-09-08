@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/</span>
        <a href="{{ route('videos.index') }}">{{ __('Videos') }}</a>
        <span class="text-muted-2">/ {{ Str::limit($video->title, 40) }}</span>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="ratio ratio-16x9 rounded overflow-hidden">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}"
                        title="{{ $video->title }}" loading="lazy"
                        allow="accelerometer; encrypted-media; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>

            <h1 class="h5 mt-3 mb-2">{{ $video->title }}</h1>
            @if ($video->description)
                <p class="small">{{ $video->description }}</p>
            @endif

            @if ($video->product)
                <div class="kj-panel p-3 mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="kj-label">{{ __('Machine in this video') }}</div>
                        <b>{{ $video->product->brand?->name }} {{ $video->product->name }}</b>
                    </div>
                    <a href="{{ route('products.show', [$video->product->brand?->slug, $video->product->slug]) }}"
                       class="btn btn-primary btn-sm">{{ __('See price & specs') }}</a>
                </div>
            @endif
        </div>

        <aside class="col-lg-4">
            <h2 class="h6 mb-2">{{ __('More videos') }}</h2>
            @foreach ($more as $other)
                <a href="{{ route('videos.show', $other->slug) }}"
                   class="d-flex gap-2 py-2 border-bottom text-decoration-none">
                    <img src="{{ $other->thumbnailUrl() }}" alt="" width="96" height="54"
                         style="object-fit: cover; border-radius:.25rem;" loading="lazy">
                    <span class="small">{{ Str::limit($other->title, 60) }}</span>
                </a>
            @endforeach
        </aside>
    </div>
</div>
@endsection

@push('schema')
@php $videoSchema = app(\App\Domain\Seo\Services\JsonLd::class)->video($video); @endphp
<script type="application/ld+json">@json($videoSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)</script>
@endpush
