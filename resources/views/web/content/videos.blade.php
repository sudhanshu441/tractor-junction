@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/ {{ __('Videos') }}</span>
    </nav>

    <h1 class="h4 mb-1">{{ __('Tractor videos') }}</h1>
    <p class="text-muted-2" style="max-width: 60ch;">
        {{ __('Field demonstrations, model comparisons and honest owner reviews — see the machine work before you commit.') }}
    </p>

    <div class="row g-3 mt-1">
        @forelse ($videos as $video)
            <div class="col-6 col-md-4 col-lg-3">
                <article class="kj-panel h-100 overflow-hidden">
                    <a href="{{ route('videos.show', $video->slug) }}" class="d-block position-relative">
                        <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}"
                             class="w-100" style="aspect-ratio: 16/9; object-fit: cover;" loading="lazy">
                        @if ($video->duration_seconds)
                            <span class="badge badge-muted position-absolute mono"
                                  style="right:.4rem; bottom:.4rem;">
                                {{ intdiv($video->duration_seconds, 60) }}:{{ str_pad($video->duration_seconds % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endif
                    </a>
                    <div class="p-2">
                        <h2 class="h6 mb-1" style="font-size:.85rem;">
                            <a href="{{ route('videos.show', $video->slug) }}" class="text-decoration-none">{{ $video->title }}</a>
                        </h2>
                        @if ($video->product)
                            <div class="small text-muted-2">{{ $video->product->brand?->name }} {{ $video->product->name }}</div>
                        @endif
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="kj-panel p-5 text-center">
                    <p class="fw-semibold mb-1">{{ __('No videos yet') }}</p>
                    <p class="small text-muted-2 mb-0">{{ __('Our team is filming.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $videos->links() }}</div>
</div>
@endsection
