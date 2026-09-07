@extends('layouts.app')

@section('title', __('New & Used Tractors in India — Price, Specs | Krishi Junction'))
@section('meta_description', __('Compare :count tractor and implement models from :brands brands. Check on-road prices, specifications and EMI across India.', ['count' => $stats['models'], 'brands' => $stats['brands']]))

@section('content')
{{-- 1. Hero + intent --}}
<section class="kj-tint border-bottom">
    <div class="container-xl py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="kj-eyebrow">{{ __("India's rural machinery marketplace") }}</div>
                <h1 class="display-6 mt-2 mb-2">{{ __('Find the right tractor, at the right price, near you.') }}</h1>
                <p class="text-muted-2 mb-4" style="max-width: 56ch;">
                    {{ __(':models models from :brands brands, with on-road prices for every state.', ['models' => number_format($stats['models']), 'brands' => $stats['brands']]) }}
                </p>

                <form method="GET" action="{{ route('search') }}" class="kj-panel p-3" role="search">
                    <div class="input-group input-group-lg">
                        <input type="search" name="q" class="form-control"
                               placeholder="{{ __('Search "Mahindra 575" or "रोटावेटर"') }}"
                               aria-label="{{ __('Search machinery') }}">
                        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                    </div>
                </form>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    <a href="{{ url('/tractors') }}" class="btn btn-sm btn-outline-primary">{{ __('Browse tractors') }}</a>
                    <a href="{{ url('/implements') }}" class="btn btn-sm btn-outline-primary">{{ __('Implements') }}</a>
                    <a href="{{ route('compare.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Compare models') }}</a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="kj-panel p-4">
                    <div class="kj-label mb-3">{{ __('Shop by budget') }}</div>
                    <div class="d-grid gap-2">
                        @foreach ($priceBands as $band)
                            <a href="{{ url('/tractors/price/'.$band['slug']) }}"
                               class="btn btn-outline-primary btn-sm text-start d-flex justify-content-between">
                                <span>{{ $band['label'] }}</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 2. Brands --}}
<section class="container-xl py-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h5 mb-1">{{ __('Browse by brand') }}</h2>
            <p class="text-muted-2 small mb-0">{{ __(':count manufacturers in the catalogue.', ['count' => $stats['brands']]) }}</p>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($brands as $brand)
            <div class="col-4 col-md-3 col-lg-2">
                <a href="{{ url('/tractors/brand/'.$brand->slug) }}" class="card h-100 text-decoration-none text-center p-3">
                    <div class="fw-semibold small">{{ $brand->name }}</div>
                    <div class="small text-muted-2">{{ $brand->country }}</div>
                </a>
            </div>
        @endforeach
    </div>
</section>

{{-- 3. Popular tractors --}}
<section class="container-xl pb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h5 mb-1">{{ __('Popular tractors') }}</h2>
            <p class="text-muted-2 small mb-0">{{ __('Ranked by views, enquiries and comparisons.') }}</p>
        </div>
        <a href="{{ url('/tractors/popular') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
    </div>

    <div class="row g-3">
        @foreach ($popular as $product)
            <div class="col-6 col-md-4 col-xl-3">
                <x-product-card :product="$product" :compare-ids="$compareIds" />
            </div>
        @endforeach
    </div>
</section>

{{-- 4. Shop by HP --}}
<section class="kj-wash border-top border-bottom py-4">
    <div class="container-xl">
        <h2 class="h6 mb-3">{{ __('Shop by horsepower') }}</h2>
        <div class="d-flex gap-2 flex-wrap">
            @foreach ($hpBands as $band)
                <a href="{{ url('/tractors/hp/'.$band['slug']) }}" class="btn btn-outline-primary btn-sm">
                    {{ $band['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- 5. Implements --}}
@if ($implements->isNotEmpty())
    <section class="container-xl py-5">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <h2 class="h5 mb-1">{{ __('Implements & attachments') }}</h2>
                <p class="text-muted-2 small mb-0">{{ __('Matched to your tractor\'s horsepower and hitch type.') }}</p>
            </div>
            <a href="{{ url('/implements') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
        </div>

        <div class="row g-3">
            @foreach ($implements as $product)
                <div class="col-6 col-md-3">
                    <x-product-card :product="$product" :compare-ids="$compareIds" />
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- 6. State price lists --}}
<section class="container-xl pb-5">
    <h2 class="h5 mb-1">{{ __('Tractor price list by state') }}</h2>
    <p class="text-muted-2 small mb-3">{{ __('On-road prices differ by state because of RTO charges and insurance.') }}</p>

    <div class="row g-2">
        @foreach ($states->take(12) as $state)
            <div class="col-6 col-md-3 col-lg-2">
                <a href="{{ route('products.price-list', $state->slug) }}"
                   class="btn btn-outline-primary btn-sm w-100">{{ $state->name }}</a>
            </div>
        @endforeach
    </div>
</section>

{{-- 7. Stats --}}
<section class="kj-tint border-top py-4">
    <div class="container-xl">
        <div class="row g-3">
            @foreach ([
                __('Models listed') => number_format($stats['models']),
                __('Brands') => $stats['brands'],
                __('States covered') => $stats['states'],
                __('Price rows') => number_format($stats['prices']),
            ] as $label => $value)
                <div class="col-6 col-lg-3">
                    <div class="kj-stat">
                        <div class="k">{{ $label }}</div>
                        <div class="v">{{ $value }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalog.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initCompare(); });</script>
@endpush
