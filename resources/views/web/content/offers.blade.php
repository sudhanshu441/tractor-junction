@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/ {{ __('Offers') }}</span>
    </nav>

    <h1 class="h4 mb-1">{{ __('Tractor offers this month') }}</h1>
    <p class="text-muted-2" style="max-width: 60ch;">
        {{ __('Cash discounts, exchange bonuses and finance schemes running right now. Every offer shows its end date — nothing here is evergreen.') }}
    </p>

    <div class="row g-3 mt-1">
        @forelse ($offers as $offer)
            <div class="col-12 col-md-6 col-lg-4">
                <article class="kj-panel h-100 d-flex flex-column overflow-hidden">
                    @if ($offer->banner_image)
                        <img src="{{ asset('storage/'.$offer->banner_image) }}" alt="{{ $offer->title }}"
                             class="w-100" style="aspect-ratio: 16/9; object-fit: cover;" loading="lazy">
                    @endif
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        @if ($offer->brand)
                            <span class="kj-label mb-1">{{ $offer->brand->name }}</span>
                        @endif
                        <h2 class="h6 mb-2">
                            <a href="{{ route('offers.show', $offer->slug) }}" class="text-decoration-none">{{ $offer->title }}</a>
                        </h2>
                        <p class="small text-muted-2 flex-grow-1">{{ Str::limit(strip_tags($offer->description), 110) }}</p>
                        @if ($offer->ends_at)
                            <p class="small mb-0">
                                <span class="badge {{ $offer->ends_at->diffInDays(now()) <= 7 ? 'badge-warn' : 'badge-muted' }}">
                                    {{ __('ends :date', ['date' => $offer->ends_at->format('d M')]) }}
                                </span>
                            </p>
                        @endif
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="kj-panel p-5 text-center">
                    <p class="fw-semibold mb-1">{{ __('No offers running right now') }}</p>
                    <p class="small text-muted-2 mb-0">{{ __('Brands publish most schemes before the sowing season.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $offers->links() }}</div>
</div>
@endsection
