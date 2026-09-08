@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/</span>
        <a href="{{ route('offers.index') }}">{{ __('Offers') }}</a>
        <span class="text-muted-2">/ {{ Str::limit($offer->title, 40) }}</span>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            @if ($offer->banner_image)
                <img src="{{ asset('storage/'.$offer->banner_image) }}" alt="{{ $offer->title }}" class="img-fluid rounded mb-3">
            @endif

            <h1 class="h4 mb-2">{{ $offer->title }}</h1>

            <div class="d-flex gap-2 flex-wrap mb-3">
                @if ($offer->brand)<span class="badge badge-muted">{{ $offer->brand->name }}</span>@endif
                <span class="badge badge-ok">{{ str_replace('_', ' ', $offer->discount_type) }}</span>
                @if ($offer->ends_at)
                    <span class="badge badge-warn">{{ __('ends :date', ['date' => $offer->ends_at->format('d M Y')]) }}</span>
                @endif
            </div>

            <div class="kj-prose">{!! $offer->description !!}</div>

            @if ($offer->terms)
                <div class="kj-panel p-3 mt-4">
                    <div class="kj-label mb-1">{{ __('Terms') }}</div>
                    <p class="small mb-0">{{ $offer->terms }}</p>
                </div>
            @endif
        </div>

        <aside class="col-lg-4">
            @if ($offer->products->isNotEmpty())
                <h2 class="h6 mb-2">{{ __('Machines in this offer') }}</h2>
                @foreach ($offer->products as $product)
                    <a href="{{ route('products.show', [$product->brand?->slug, $product->slug]) }}"
                       class="d-block kj-panel p-2 mb-2 text-decoration-none">
                        <b class="small">{{ $product->brand?->name }} {{ $product->name }}</b>
                    </a>
                @endforeach
            @endif

            <div class="kj-panel p-3 mt-3">
                <h2 class="h6 mb-2">{{ __('Want this offer?') }}</h2>
                <p class="small text-muted-2">{{ __('Leave your number and a dealer near you will call with the final on-road price.') }}</p>
                <a href="{{ route('dealers.index') }}" class="btn btn-primary btn-sm w-100">{{ __('Find a dealer') }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection
