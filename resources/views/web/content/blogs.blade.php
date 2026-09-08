@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/</span>
        @if ($category)
            <a href="{{ route('blogs.index') }}">{{ __('News') }}</a>
            <span class="text-muted-2">/ {{ $category->name }}</span>
        @else
            <span class="text-muted-2">{{ __('News') }}</span>
        @endif
    </nav>

    <h1 class="h4 mb-1">
        {{ $category ? $category->name : __('Tractor news, reviews and farming guides') }}
    </h1>
    <p class="text-muted-2" style="max-width: 62ch;">
        {{ __('Launches, price changes, government schemes and buying advice — written for people who actually work the land.') }}
    </p>

    <div class="d-flex gap-2 flex-wrap my-3">
        <a href="{{ route('blogs.index') }}"
           class="btn btn-sm {{ $category ? 'btn-outline-secondary' : 'btn-primary' }}">{{ __('All') }}</a>
        @foreach ($categories as $item)
            <a href="{{ route('blogs.category', $item->slug) }}"
               class="btn btn-sm {{ $category?->id === $item->id ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $item->name }}
            </a>
        @endforeach
    </div>

    @if ($featured)
        <article class="kj-panel p-0 overflow-hidden mb-4">
            <div class="row g-0">
                <div class="col-md-5">
                    @if ($featured->cover_image)
                        <img src="{{ asset('storage/'.$featured->cover_image) }}" alt="{{ $featured->title }}"
                             class="w-100 h-100" style="object-fit: cover; min-height: 220px;">
                    @else
                        <div class="kj-tint h-100" style="min-height: 220px;"></div>
                    @endif
                </div>
                <div class="col-md-7 p-4">
                    <span class="badge badge-ok">{{ __('Featured') }}</span>
                    @if ($featured->category)
                        <span class="badge badge-muted">{{ $featured->category->name }}</span>
                    @endif
                    <h2 class="h5 mt-2 mb-2">
                        <a href="{{ route('blogs.show', $featured->slug) }}" class="text-decoration-none">{{ $featured->title }}</a>
                    </h2>
                    <p class="small text-muted-2 mb-2">{{ $featured->excerpt }}</p>
                    <p class="small text-muted-2 mb-0">
                        {{ $featured->published_at?->format('d M Y') }} ·
                        {{ trans_choice('{1} :count min read|[2,*] :count min read', $featured->reading_minutes, ['count' => $featured->reading_minutes]) }}
                    </p>
                </div>
            </div>
        </article>
    @endif

    <div class="row g-3">
        @forelse ($posts as $post)
            <div class="col-12 col-sm-6 col-lg-4">
                <article class="kj-panel h-100 d-flex flex-column overflow-hidden">
                    <a href="{{ route('blogs.show', $post->slug) }}" class="d-block">
                        @if ($post->cover_image)
                            <img src="{{ asset('storage/'.$post->cover_image) }}" alt="{{ $post->title }}"
                                 class="w-100" style="aspect-ratio: 16/9; object-fit: cover;" loading="lazy">
                        @else
                            <div class="kj-tint" style="aspect-ratio: 16/9;"></div>
                        @endif
                    </a>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        @if ($post->category)
                            <span class="kj-label mb-1">{{ $post->category->name }}</span>
                        @endif
                        <h2 class="h6 mb-2">
                            <a href="{{ route('blogs.show', $post->slug) }}" class="text-decoration-none">{{ $post->title }}</a>
                        </h2>
                        <p class="small text-muted-2 flex-grow-1">{{ Str::limit($post->excerpt, 110) }}</p>
                        <p class="small text-muted-2 mb-0">
                            {{ $post->published_at?->format('d M Y') }} ·
                            {{ trans_choice('{1} :count min read|[2,*] :count min read', $post->reading_minutes, ['count' => $post->reading_minutes]) }}
                        </p>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="kj-panel p-5 text-center">
                    <p class="fw-semibold mb-1">{{ __('Nothing published here yet') }}</p>
                    <p class="small text-muted-2 mb-0">{{ __('Come back shortly — our team writes every week.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $posts->links() }}</div>
</div>
@endsection
