@extends('layouts.app')

@section('title', ($brand ?? null) ? $heading.' — Price, Specs | Krishi Junction' : $heading.' in India — Price List 2026 | Krishi Junction')
@section('meta_description', __('Compare :heading with prices, specifications, mileage and dealer details across India.', ['heading' => strtolower($heading)]))

@section('content')
<div class="container-xl py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $heading }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $heading }}</h1>
            <p class="text-muted-2 small mb-0" id="result-summary">
                {{ trans_choice(':count model|:count models', $products->total(), ['count' => number_format($products->total())]) }}
                @if ($facets['total'] ?? null)
                    · {{ __('prices from :price', ['price' => \App\Domain\Catalog\Services\PriceService::inLakh($products->min('price_min'))]) }}
                @endif
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <label class="kj-label mb-0 d-none d-md-inline" for="sort">{{ __('Sort') }}</label>
            <select id="sort" name="sort" class="form-select form-select-sm js-filter-input" style="width:auto;">
                @foreach ([
                    'popular' => __('Popularity'), 'price-low' => __('Price: low to high'),
                    'price-high' => __('Price: high to low'), 'hp-low' => __('HP: low to high'),
                    'hp-high' => __('HP: high to low'), 'newest' => __('Newest first'),
                    'rating' => __('Top rated'),
                ] as $value => $label)
                    <option value="{{ $value }}" @selected($filter->sort() === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn btn-outline-primary btn-sm d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#filterPanel">
                {{ __('Filters') }}
                @if (count($activeFilters))
                    <span class="badge badge-ok ms-1">{{ count($activeFilters) }}</span>
                @endif
            </button>
        </div>
    </div>

    <form id="filter-form" method="GET" action="{{ url()->current() }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="row g-4">
            {{-- Filters: an offcanvas on mobile, a column on desktop --}}
            <div class="col-lg-3">
                <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="filterPanel"
                     aria-labelledby="filterPanelLabel">
                    <div class="offcanvas-header">
                        <h2 class="offcanvas-title h6" id="filterPanelLabel">{{ __('Filters') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                data-bs-target="#filterPanel" aria-label="{{ __('Close') }}"></button>
                    </div>

                    <div class="offcanvas-body d-block" id="facet-panel">
                        @include('web.products.partials.filters')
                    </div>
                </div>
            </div>

            {{-- Results --}}
            <div class="col-lg-9">
                @if (count($activeFilters))
                    <div class="d-flex gap-2 flex-wrap mb-3" id="active-chips">
                        @foreach ($activeFilters as $key => $value)
                            @continue($key === 'sort')
                            <span class="badge badge-ok d-inline-flex align-items-center gap-2">
                                {{ str_replace('_', ' ', $key) }}: {{ is_array($value) ? implode(', ', $value) : $value }}
                                <button type="button" class="btn-close" style="font-size:.5rem"
                                        aria-label="{{ __('Remove filter') }}"
                                        data-filter-key="{{ $key }}"></button>
                            </span>
                        @endforeach
                        <button type="button" class="btn btn-link btn-sm p-0 js-clear-filters">{{ __('Clear all') }}</button>
                    </div>
                @endif

                <div class="row g-3" id="result-grid">
                    @include('partials.ajax.product-grid', ['products' => $products, 'compareIds' => $compareIds])
                </div>

                <div id="result-pagination" class="mt-4">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </form>

    {{-- SEO block: real copy for the reader, and the internal links crawlers follow --}}
    <section class="kj-panel p-4 mt-5">
        <h2 class="h6">{{ __('Buying a :heading in India', ['heading' => \Illuminate\Support\Str::singular(strtolower($heading))]) }}</h2>
        <p class="small text-muted-2 mb-3">
            {{ __('Prices shown are ex-showroom and vary by state because of RTO charges and insurance. Open any model to see the on-road price for your city, calculate an EMI, and find dealers near you.') }}
        </p>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="kj-label mb-2">{{ __('By horsepower') }}</div>
                <ul class="list-unstyled small mb-0">
                    @foreach (app(\App\Domain\Catalog\Services\FacetService::class)->hpBands() as $band)
                        <li><a href="{{ url('/tractors/hp/'.$band['slug']) }}" class="text-decoration-none">{{ $band['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-4">
                <div class="kj-label mb-2">{{ __('By budget') }}</div>
                <ul class="list-unstyled small mb-0">
                    @foreach (app(\App\Domain\Catalog\Services\FacetService::class)->priceBands() as $band)
                        <li><a href="{{ url('/tractors/price/'.$band['slug']) }}" class="text-decoration-none">{{ $band['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-4">
                <div class="kj-label mb-2">{{ __('Popular brands') }}</div>
                <ul class="list-unstyled small mb-0">
                    @foreach (($facets['brands'] ?? []) as $brandFacet)
                        @if ($loop->index < 6)
                            <li><a href="{{ url('/tractors/brand/'.$brandFacet['slug']) }}" class="text-decoration-none">{{ $brandFacet['name'] }} ({{ $brandFacet['count'] }})</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
</div>

<div id="compare-bar-host">
    @include('partials.ajax.compare-bar', ['products' => app(\App\Domain\Catalog\Services\CompareService::class)->products()])
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalog.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initListing({ endpoint: '{{ route('ajax.products.filter') }}', type: '{{ $type }}' }); });</script>
@endpush
