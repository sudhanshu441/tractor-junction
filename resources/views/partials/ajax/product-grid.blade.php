@forelse ($products as $product)
    <div class="col-6 col-md-4 col-xl-3">
        <x-product-card :product="$product" :compare-ids="$compareIds" />
    </div>
@empty
    <div class="col-12">
        <div class="kj-panel p-5 text-center">
            <p class="fw-semibold mb-1">{{ __('No machinery matches these filters') }}</p>
            <p class="text-muted-2 small mb-3">{{ __('Try widening the price or HP range, or clearing a brand.') }}</p>
            <button type="button" class="btn btn-outline-primary btn-sm js-clear-filters">{{ __('Clear all filters') }}</button>
        </div>
    </div>
@endforelse
