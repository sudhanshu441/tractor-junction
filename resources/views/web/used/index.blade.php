@extends('layouts.app')

@section('title', $heading.' — '.__('Price, Photos & Contact | Krishi Junction'))
@section('meta_description', __('Browse verified second-hand tractors and implements with photos, engine hours, inspection reports and seller contact.'))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('used.index') }}">{{ __('Used') }}</a></li>
            @if ($state)<li class="breadcrumb-item active">{{ $district?->name ?? $state->name }}</li>@endif
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $heading }}</h1>
            <p class="text-muted-2 small mb-0" id="result-summary">
                {{ trans_choice(':count listing|:count listings', $listings->total(), ['count' => number_format($listings->total())]) }}
                @if ($facets['verified'] > 0)
                    · {{ __(':count inspected', ['count' => $facets['verified']]) }}
                @endif
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <select id="sort" name="sort" class="form-select form-select-sm js-filter-input" style="width:auto;">
                @foreach ([
                    'newest' => __('Newest first'), 'price-low' => __('Price: low to high'),
                    'price-high' => __('Price: high to low'), 'year-new' => __('Year: newest'),
                    'hours-low' => __('Hours: lowest'),
                ] as $value => $label)
                    <option value="{{ $value }}" @selected($filter->sort() === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <a href="{{ route('sell.start') }}" class="btn btn-deep btn-sm">{{ __('Sell yours') }}</a>
            <button class="btn btn-outline-primary btn-sm d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#filterPanel">{{ __('Filters') }}</button>
        </div>
    </div>

    <form id="filter-form" method="GET" action="{{ url()->current() }}">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="filterPanel">
                    <div class="offcanvas-header">
                        <h2 class="offcanvas-title h6">{{ __('Filters') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                data-bs-target="#filterPanel" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="offcanvas-body d-block" id="facet-panel">
                        <div class="kj-panel p-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input js-filter-input" type="checkbox" name="verified"
                                       value="1" id="verified" @checked(request('verified'))>
                                <label class="form-check-label small fw-semibold" for="verified">
                                    {{ __('Inspected only') }}
                                    <span class="text-muted-2 mono">{{ $facets['verified'] }}</span>
                                </label>
                            </div>
                            <p class="form-text mb-0">{{ __('Checked in person by our inspector.') }}</p>
                        </div>

                        <div class="kj-panel p-3 mb-3">
                            <h3 class="h6 mb-2">{{ __('Budget') }}</h3>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm js-filter-input"
                                           name="price_min" placeholder="{{ __('Min ₹') }}" value="{{ request('price_min') }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm js-filter-input"
                                           name="price_max" placeholder="{{ __('Max ₹') }}" value="{{ request('price_max') }}">
                                </div>
                            </div>
                        </div>

                        <div class="kj-panel p-3 mb-3">
                            <h3 class="h6 mb-2">{{ __('Year') }}</h3>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" min="1990" max="{{ date('Y') }}" class="form-control form-control-sm js-filter-input"
                                           name="year_min" placeholder="{{ __('From') }}" value="{{ request('year_min') }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" min="1990" max="{{ date('Y') }}" class="form-control form-control-sm js-filter-input"
                                           name="year_max" placeholder="{{ __('To') }}" value="{{ request('year_max') }}">
                                </div>
                            </div>
                        </div>

                        <div class="kj-panel p-3 mb-3">
                            <h3 class="h6 mb-2">{{ __('Engine hours') }}</h3>
                            @foreach ([2000 => __('Under 2,000'), 4000 => __('Under 4,000'), 6000 => __('Under 6,000')] as $value => $label)
                                <div class="form-check">
                                    <input class="form-check-input js-filter-input" type="radio" name="hours_max"
                                           value="{{ $value }}" id="hours-{{ $value }}" @checked(request('hours_max') == $value)>
                                    <label class="form-check-label small" for="hours-{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>

                        @if (count($facets['brands']))
                            <div class="kj-panel p-3 mb-3">
                                <h3 class="h6 mb-2">{{ __('Brand') }}</h3>
                                @foreach ($facets['brands'] as $brandFacet)
                                    <div class="form-check">
                                        <input class="form-check-input js-filter-input" type="checkbox" name="brand[]"
                                               value="{{ $brandFacet['id'] }}" id="ub-{{ $brandFacet['id'] }}"
                                               @checked(in_array((string) $brandFacet['id'], (array) request('brand', []), true))>
                                        <label class="form-check-label small d-flex justify-content-between" for="ub-{{ $brandFacet['id'] }}">
                                            <span>{{ $brandFacet['name'] }}</span>
                                            <span class="text-muted-2 mono">{{ $brandFacet['count'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="kj-panel p-3">
                            <h3 class="h6 mb-2">{{ __('Condition') }}</h3>
                            @foreach ($facets['conditions'] as $condition)
                                <div class="form-check">
                                    <input class="form-check-input js-filter-input" type="checkbox" name="condition[]"
                                           value="{{ $condition['value'] }}" id="cond-{{ $condition['value'] }}"
                                           @checked(in_array($condition['value'], (array) request('condition', []), true))>
                                    <label class="form-check-label small d-flex justify-content-between" for="cond-{{ $condition['value'] }}">
                                        <span>{{ $condition['label'] }}</span>
                                        <span class="text-muted-2 mono">{{ $condition['count'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="row g-3" id="result-grid">
                    @include('partials.ajax.used-grid', ['listings' => $listings])
                </div>
                <div id="result-pagination" class="mt-4">{{ $listings->onEachSide(1)->links() }}</div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalog.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initListing({ endpoint: '{{ route('ajax.used.filter') }}', type: 'used' }); });</script>
@endpush
