@php $facetBrands = $facets['brands'] ?? []; @endphp

<div class="kj-panel p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h6 mb-0">{{ __('Brand') }}</h3>
        @if (count($facetBrands) > 8)
            <button type="button" class="btn btn-link btn-sm p-0 js-toggle-more" data-more-group="brand">{{ __('Show all') }}</button>
        @endif
    </div>

    @foreach ($facetBrands as $i => $brandFacet)
        <div class="form-check {{ $i >= 8 ? 'd-none js-more' : '' }} {{ $brandFacet['count'] === 0 && ! $brandFacet['checked'] ? 'opacity-50' : '' }}"
             @if ($i >= 8) data-more-group="brand" @endif>
            <input class="form-check-input js-filter-input" type="checkbox" name="brand[]"
                   value="{{ $brandFacet['id'] }}" id="brand-{{ $brandFacet['id'] }}"
                   @checked($brandFacet['checked'])
                   @disabled($brandFacet['count'] === 0 && ! $brandFacet['checked'])>
            <label class="form-check-label small d-flex justify-content-between" for="brand-{{ $brandFacet['id'] }}">
                <span>{{ $brandFacet['name'] }}</span>
                <span class="text-muted-2 mono">{{ $brandFacet['count'] }}</span>
            </label>
        </div>
    @endforeach

    @if (! count($facetBrands))
        <p class="small text-muted-2 mb-0">{{ __('No brands to filter yet.') }}</p>
    @endif
</div>

<div class="kj-panel p-3 mb-3">
    <h3 class="h6 mb-2">{{ __('Horsepower') }}</h3>
    <div class="row g-2">
        <div class="col-6">
            <label class="kj-label" for="hp_min">{{ __('Min HP') }}</label>
            <input type="number" min="0" max="999" class="form-control form-control-sm js-filter-input"
                   id="hp_min" name="hp_min" value="{{ request('hp_min') }}" placeholder="0">
        </div>
        <div class="col-6">
            <label class="kj-label" for="hp_max">{{ __('Max HP') }}</label>
            <input type="number" min="0" max="999" class="form-control form-control-sm js-filter-input"
                   id="hp_max" name="hp_max" value="{{ request('hp_max') }}" placeholder="100">
        </div>
    </div>
    <div class="d-flex gap-1 flex-wrap mt-2">
        @foreach (($facets['hp_bands'] ?? []) as $band)
            <button type="button" class="btn btn-sm btn-outline-primary js-band"
                    data-min-field="hp_min" data-max-field="hp_max"
                    data-min="{{ $band['min'] }}" data-max="{{ $band['max'] }}"
                    @disabled($band['count'] === 0)>
                {{ $band['label'] }} <span class="text-muted-2">{{ $band['count'] }}</span>
            </button>
        @endforeach
    </div>
</div>

<div class="kj-panel p-3 mb-3">
    <h3 class="h6 mb-2">{{ __('Budget') }}</h3>
    <div class="d-flex gap-1 flex-wrap">
        @foreach (($facets['price_bands'] ?? []) as $band)
            <button type="button" class="btn btn-sm btn-outline-primary js-band"
                    data-min-field="price_min" data-max-field="price_max"
                    data-min="{{ $band['min'] }}" data-max="{{ $band['max'] }}"
                    @disabled($band['count'] === 0)>
                {{ $band['label'] }} <span class="text-muted-2">{{ $band['count'] }}</span>
            </button>
        @endforeach
    </div>
    <input type="hidden" name="price_min" id="price_min" class="js-filter-input" value="{{ request('price_min') }}">
    <input type="hidden" name="price_max" id="price_max" class="js-filter-input" value="{{ request('price_max') }}">
</div>

@if (count($facets['wheel_drive'] ?? []))
    <div class="kj-panel p-3 mb-3">
        <h3 class="h6 mb-2">{{ __('Wheel drive') }}</h3>
        @foreach ($facets['wheel_drive'] as $option)
            <div class="form-check">
                <input class="form-check-input js-filter-input" type="checkbox" name="wheel_drive[]"
                       value="{{ $option['value'] }}" id="wd-{{ $loop->index }}"
                       @checked(in_array($option['value'], (array) request('wheel_drive', []), true))>
                <label class="form-check-label small d-flex justify-content-between" for="wd-{{ $loop->index }}">
                    <span>{{ $option['value'] }}</span>
                    <span class="text-muted-2 mono">{{ $option['count'] }}</span>
                </label>
            </div>
        @endforeach
    </div>
@endif

<div class="kj-panel p-3">
    <h3 class="h6 mb-2">{{ __('Features') }}</h3>
    <div class="form-check">
        <input class="form-check-input js-filter-input" type="checkbox" name="power_steering" value="1"
               id="power_steering" @checked(request('power_steering'))>
        <label class="form-check-label small d-flex justify-content-between" for="power_steering">
            <span>{{ __('Power steering') }}</span>
            <span class="text-muted-2 mono">{{ $facets['features']['power_steering'] ?? 0 }}</span>
        </label>
    </div>
    <div class="form-check">
        <input class="form-check-input js-filter-input" type="checkbox" name="ac_cabin" value="1"
               id="ac_cabin" @checked(request('ac_cabin'))>
        <label class="form-check-label small d-flex justify-content-between" for="ac_cabin">
            <span>{{ __('AC cabin') }}</span>
            <span class="text-muted-2 mono">{{ $facets['features']['ac_cabin'] ?? 0 }}</span>
        </label>
    </div>
</div>
