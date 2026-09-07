@props(['listing'])

@php $image = $listing->primary_image; @endphp

<article class="card h-100 kj-product-card">
    <a href="{{ route('used.show', $listing->slug) }}" class="text-decoration-none text-reset">
        <div class="ratio kj-thumb" style="--bs-aspect-ratio: 75%;">
            @if ($image)
                <img src="{{ $image->thumbnailUrl() }}" alt="{{ $listing->title }}" loading="lazy" class="object-fit-cover">
            @else
                <span class="d-flex align-items-center justify-content-center">@include('partials.machine-icon')</span>
            @endif
        </div>
    </a>

    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <h3 class="h6 mb-1">
                <a href="{{ route('used.show', $listing->slug) }}" class="text-decoration-none text-reset">
                    {{ $listing->title }}
                </a>
            </h3>
            <span class="badge badge-muted">{{ $listing->manufacturing_year }}</span>
        </div>

        <div class="small text-muted-2 mono mb-2">
            {{ $listing->engine_hours ? number_format($listing->engine_hours).' '.__('hrs') : __('hours not stated') }}
            · {{ $listing->city?->name ?? $listing->district?->name }}
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2">
            <span class="fw-bold" style="font-family:Archivo,sans-serif;">
                ₹{{ number_format((float) $listing->expected_price) }}
            </span>
            @if ($listing->is_verified)
                <span class="badge badge-ok">{{ __('Inspected') }}</span>
            @elseif ($listing->seller_type === 'dealer')
                <span class="badge badge-info">{{ __('Dealer') }}</span>
            @elseif ($listing->is_price_negotiable)
                <span class="small text-muted-2">{{ __('Negotiable') }}</span>
            @endif
        </div>
    </div>
</article>
