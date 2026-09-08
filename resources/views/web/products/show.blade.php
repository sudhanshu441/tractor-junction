@extends('layouts.app')

@php
    $priceService = app(\App\Domain\Catalog\Services\PriceService::class);
    $image = $product->primary_image;
@endphp

@section('title', $product->full_name.' '.__('Price 2026, Specifications, Mileage').' | Krishi Junction')
@section('meta_description', $product->short_description
    ?: __(':name price starts at :price. Check specifications, mileage, on-road price in your city, EMI and dealers.', [
        'name' => $product->full_name,
        'price' => \App\Domain\Catalog\Services\PriceService::inLakh($product->price_min),
    ]))

@push('styles')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->full_name,
    'brand' => ['@type' => 'Brand', 'name' => $product->brand->name],
    'description' => $product->short_description,
    'offers' => $product->price_min ? [
        '@type' => 'AggregateOffer',
        'priceCurrency' => 'INR',
        'lowPrice' => (float) $product->price_min,
        'highPrice' => (float) ($product->price_max ?: $product->price_min),
        'availability' => $product->status === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
    ] : null,
    'aggregateRating' => $product->rating_count > 0 ? [
        '@type' => 'AggregateRating',
        'ratingValue' => (float) $product->rating_avg,
        'reviewCount' => $product->rating_count,
    ] : null,
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/tractors') }}">{{ __('Tractors') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/tractors/brand/'.$product->brand->slug) }}">{{ $product->brand->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="kj-panel p-2">
                <div class="ratio kj-thumb rounded" style="--bs-aspect-ratio: 68%;">
                    @if ($image)
                        <img src="{{ $image->url('detail') }}" alt="{{ $image->alt_text ?: $product->full_name }}"
                             width="1200" height="816" class="object-fit-cover rounded">
                    @else
                        <span class="d-flex align-items-center justify-content-center">@include('partials.machine-icon')</span>
                    @endif
                </div>

                @if ($product->media->count() > 1)
                    <div class="d-flex gap-2 mt-2 overflow-auto">
                        @foreach ($product->media as $item)
                            <img src="{{ $item->url('thumb') }}" alt="{{ $item->alt_text ?: $product->full_name }}"
                                 width="72" height="54" loading="lazy"
                                 class="rounded border object-fit-cover js-gallery-thumb"
                                 data-full="{{ $item->url('detail') }}" style="cursor:pointer;">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="d-flex gap-2 flex-wrap mb-2">
                <span class="badge {{ $product->status === 'available' ? 'badge-ok' : 'badge-warn' }}">
                    {{ ucfirst($product->status) }}
                </span>
                @if ($product->rating_count > 0)
                    <span class="badge badge-muted">{{ number_format($product->rating_avg, 1) }} ★ ({{ $product->rating_count }})</span>
                @endif
                @if ($product->is_popular)
                    <span class="badge badge-ok">{{ __('Popular') }}</span>
                @endif
            </div>

            <h1 class="h3 mb-1">{{ $product->full_name }}</h1>
            <p class="text-muted-2 small">
                {{ collect([$product->hp_label, $product->category?->name, $product->model_code])->filter()->implode(' · ') }}
            </p>

            <div class="my-3">
                <div class="kj-label">{{ __('Ex-showroom price') }}</div>
                <div class="h3 mb-0" style="font-family:Archivo,sans-serif;">
                    {{ \App\Domain\Catalog\Services\PriceService::range($product->price_min, $product->price_max) }}
                </div>
            </div>

            @if ($price)
                <div class="kj-panel p-3">
                    <div class="kj-label mb-2">
                        {{ __('On-road price') }}{{ $price->state ? ' — '.$price->state->name : ' — '.__('national estimate') }}
                    </div>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td>{{ __('Ex-showroom') }}</td><td class="num">₹{{ number_format((float) $price->ex_showroom) }}</td></tr>
                            <tr><td>{{ __('RTO & registration') }}</td><td class="num">₹{{ number_format((float) $price->rto_charges) }}</td></tr>
                            <tr><td>{{ __('Insurance') }}</td><td class="num">₹{{ number_format((float) $price->insurance_amount) }}</td></tr>
                            @if ((float) $price->other_charges > 0)
                                <tr><td>{{ __('Other charges') }}</td><td class="num">₹{{ number_format((float) $price->other_charges) }}</td></tr>
                            @endif
                            <tr class="fw-bold"><td>{{ __('On-road') }}</td><td class="num">₹{{ number_format((float) $price->on_road_price) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="d-flex gap-2 flex-wrap mt-3">
                <button class="btn btn-primary" type="button" disabled title="{{ __('Enquiries arrive in Phase 3') }}">{{ __('Get best price') }}</button>
                <button class="btn btn-outline-primary js-compare-toggle" type="button"
                        data-product-id="{{ $product->id }}"
                        aria-pressed="{{ in_array($product->id, $compareIds, true) ? 'true' : 'false' }}">
                    {{ in_array($product->id, $compareIds, true) ? __('Added to compare') : __('Add to compare') }}
                </button>
                @if ($product->brochure_path)
                    <a class="btn btn-outline-primary" href="{{ asset('storage/'.$product->brochure_path) }}" download>{{ __('Brochure') }}</a>
                @endif
            </div>
        </div>
    </div>

    @if ($keySpecs->isNotEmpty())
        <section class="mt-5">
            <h2 class="h5 mb-3">{{ __('Key specifications') }}</h2>
            <div class="row g-3">
                @foreach ($keySpecs as $spec)
                    <div class="col-6 col-md-3">
                        <div class="kj-stat">
                            <div class="k">{{ $spec->attribute->name }}</div>
                            <div class="v" style="font-size:1.05rem;">
                                {{ $spec->value }} {{ $spec->attribute->unit }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="row g-4 mt-2">
        <div class="col-lg-8">
            @if ($specGroups->isNotEmpty())
                <section class="kj-panel p-4">
                    <h2 class="h5 mb-3">{{ __(':name specifications', ['name' => $product->full_name]) }}</h2>
                    @foreach ($specGroups as $groupName => $values)
                        <div class="kj-label mt-3 mb-2">{{ $groupName }}</div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($values as $spec)
                                    <tr>
                                        <td class="text-muted-2" style="width:45%">{{ $spec->attribute->name }}</td>
                                        <td class="mono">{{ $spec->value }} {{ $spec->attribute->unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </section>
            @endif

            @if ($product->description)
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h5 mb-2">{{ __('About the :name', ['name' => $product->full_name]) }}</h2>
                    <div class="small">{!! nl2br(e($product->description)) !!}</div>
                </section>
            @endif

            @if ($product->features->isNotEmpty())
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h5 mb-3">{{ __('Features') }}</h2>
                    <div class="row g-3">
                        @foreach ($product->features as $feature)
                            <div class="col-md-6">
                                <div class="fw-semibold small">{{ $feature->title }}</div>
                                @if ($feature->description)
                                    <p class="small text-muted-2 mb-0">{{ $feature->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($product->faqs->isNotEmpty())
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h5 mb-3">{{ __('Frequently asked questions') }}</h2>
                    <div class="accordion" id="productFaqs">
                        @foreach ($product->faqs as $faq)
                            <div class="accordion-item">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#faq-{{ $faq->id }}"
                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        {{ $faq->question }}
                                    </button>
                                </h3>
                                <div id="faq-{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                     data-bs-parent="#productFaqs">
                                    <div class="accordion-body small">{{ $faq->answer }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <div class="col-lg-4">
            @if ($statePrices->isNotEmpty())
                <section class="kj-panel p-3">
                    <h2 class="h6 mb-2">{{ __('On-road price by state') }}</h2>
                    <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($statePrices as $row)
                                    <tr>
                                        <td class="small">{{ $row->state?->name }}</td>
                                        <td class="num small">₹{{ number_format((float) $row->on_road_price) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($product->videos->isNotEmpty())
                <section class="kj-panel p-3 mt-3">
                    <h2 class="h6 mb-2">{{ __('Videos') }}</h2>
                    @foreach ($product->videos as $video)
                        <div class="ratio ratio-16x9 mb-2">
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}"
                                    title="{{ $video->title }}" loading="lazy"
                                    allow="accelerometer; encrypted-media; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                        </div>
                    @endforeach
                </section>
            @endif
        </div>
    </div>

    @include('partials.reviews', [
        'subject' => $product,
        'subjectType' => 'product',
        'reviewAspects' => \App\Domain\Engagement\Services\ReviewService::ASPECTS['product'],
    ])

    @if ($competitors->isNotEmpty() || $similar->isNotEmpty())
        <section class="mt-5">
            <h2 class="h5 mb-3">{{ $competitors->isNotEmpty() ? __('Compare with similar tractors') : __('Similar tractors') }}</h2>
            <div class="row g-3">
                @foreach (($competitors->isNotEmpty() ? $competitors : $similar) as $other)
                    <div class="col-6 col-md-3">
                        <x-product-card :product="$other" :compare-ids="$compareIds" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

<div id="compare-bar-host">
    @include('partials.ajax.compare-bar', ['products' => app(\App\Domain\Catalog\Services\CompareService::class)->products()])
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalog.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initDetail(); });</script>
@endpush
