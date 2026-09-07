@props(['product', 'compareIds' => []])

@php
    $image = $product->primary_image;
    $inCompare = in_array($product->id, $compareIds, true);
    $url = route('products.show', [$product->brand->slug, $product->slug]);
@endphp

<article class="card h-100 kj-product-card">
    <a href="{{ $url }}" class="text-decoration-none text-reset">
        <div class="ratio kj-thumb" style="--bs-aspect-ratio: 75%;">
            @if ($image)
                <img src="{{ $image->url('card') }}" alt="{{ $image->alt_text ?: $product->full_name }}"
                     loading="lazy" width="600" height="450" class="object-fit-cover">
            @else
                <span class="d-flex align-items-center justify-content-center text-muted-2 small">
                    @include('partials.machine-icon')
                </span>
            @endif
        </div>
    </a>

    <div class="card-body p-3">
        <div class="small text-muted-2 mono">{{ $product->brand->name }}</div>
        <h3 class="h6 mb-1">
            <a href="{{ $url }}" class="text-decoration-none text-reset stretched-link-none">{{ $product->name }}</a>
        </h3>

        <div class="d-flex justify-content-between align-items-center gap-2">
            <span class="fw-bold" style="font-family:Archivo,sans-serif;">
                {{ \App\Domain\Catalog\Services\PriceService::range($product->price_min, $product->price_max) }}
            </span>
            @if ($product->hp_label)
                <span class="badge badge-muted">{{ $product->hp_label }}</span>
            @endif
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2 gap-2">
            @if ($product->rating_count > 0)
                <span class="badge badge-ok">{{ number_format($product->rating_avg, 1) }} ★ ({{ $product->rating_count }})</span>
            @elseif ($product->status === 'upcoming')
                <span class="badge badge-warn">{{ __('Upcoming') }}</span>
            @else
                <span></span>
            @endif

            <button type="button"
                    class="btn btn-sm {{ $inCompare ? 'btn-primary' : 'btn-outline-primary' }} js-compare-toggle"
                    data-product-id="{{ $product->id }}"
                    aria-pressed="{{ $inCompare ? 'true' : 'false' }}">
                {{ $inCompare ? __('Added') : __('Compare') }}
            </button>
        </div>
    </div>
</article>
