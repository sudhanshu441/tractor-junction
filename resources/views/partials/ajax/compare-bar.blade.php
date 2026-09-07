@if ($products->isNotEmpty())
    <div class="kj-compare-bar">
        <div class="container-xl d-flex align-items-center gap-3 flex-wrap py-2">
            <span class="kj-label mb-0">{{ trans_choice(':count model to compare|:count models to compare', $products->count(), ['count' => $products->count()]) }}</span>

            <div class="d-flex gap-2 flex-wrap flex-grow-1">
                @foreach ($products as $product)
                    <span class="badge badge-muted d-inline-flex align-items-center gap-2">
                        {{ $product->full_name }}
                        <button type="button" class="btn-close btn-close-sm js-compare-remove"
                                style="font-size:.5rem" aria-label="{{ __('Remove') }}"
                                data-product-id="{{ $product->id }}"></button>
                    </span>
                @endforeach
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary js-compare-clear">{{ __('Clear') }}</button>
                @if ($products->count() >= 2)
                    <a href="{{ route('compare.show', app(\App\Domain\Catalog\Services\CompareService::class)->slugFor($products)) }}"
                       class="btn btn-sm btn-primary">{{ __('Compare now') }}</a>
                @else
                    <button class="btn btn-sm btn-primary" disabled>{{ __('Add 1 more') }}</button>
                @endif
            </div>
        </div>
    </div>
@endif
