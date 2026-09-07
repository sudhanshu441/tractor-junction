@forelse ($listings as $listing)
    <div class="col-6 col-md-4 col-xl-3">
        <x-used-card :listing="$listing" />
    </div>
@empty
    <div class="col-12">
        <div class="kj-panel p-5 text-center">
            <p class="fw-semibold mb-1">{{ __('No machinery matches these filters') }}</p>
            <p class="text-muted-2 small mb-3">{{ __('Try a wider price range, or a neighbouring district.') }}</p>
            <button type="button" class="btn btn-outline-primary btn-sm js-clear-filters">{{ __('Clear all filters') }}</button>
        </div>
    </div>
@endforelse
