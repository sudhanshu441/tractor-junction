@extends('layouts.app')

@php $names = $products->map(fn ($p) => $p->full_name); @endphp

@section('title', $names->implode(' vs ').' — '.__('Compare Price, Specs & Mileage').' | Krishi Junction')
@section('meta_description', __('Side-by-side comparison of :names — price, engine, HP, lift capacity, transmission and features.', ['names' => $names->implode(' vs ')]))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('compare.index') }}">{{ __('Compare') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $names->implode(' vs ') }}</li>
        </ol>
    </nav>

    <h1 class="h4 mb-1">{{ $names->implode(' vs ') }}</h1>
    <p class="text-muted-2 small mb-4">
        {{ trans_choice(':count model compared|:count models compared', $products->count(), ['count' => $products->count()]) }}
        · {{ __('rows where the models differ are highlighted') }}
    </p>

    <div class="table-responsive kj-panel">
        <table class="table table-sm align-middle mb-0 kj-compare-table">
            <thead>
                <tr>
                    <th style="min-width:170px;">{{ __('Specification') }}</th>
                    @foreach ($products as $product)
                        <th style="min-width:190px;">
                            <div class="ratio kj-thumb rounded mb-2" style="--bs-aspect-ratio:70%; max-width:150px;">
                                @if ($product->primary_image)
                                    <img src="{{ $product->primary_image->url('card') }}" alt="{{ $product->full_name }}"
                                         class="object-fit-cover rounded" loading="lazy">
                                @else
                                    <span class="d-flex align-items-center justify-content-center">@include('partials.machine-icon')</span>
                                @endif
                            </div>
                            <a href="{{ route('products.show', [$product->brand->slug, $product->slug]) }}"
                               class="text-decoration-none">{{ $product->full_name }}</a>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="kj-diff">
                    <td class="text-muted-2">{{ __('Ex-showroom price') }}</td>
                    @foreach ($products as $product)
                        <td class="fw-bold">{{ \App\Domain\Catalog\Services\PriceService::range($product->price_min, $product->price_max) }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="text-muted-2">{{ __('On-road (estimate)') }}</td>
                    @foreach ($products as $product)
                        <td class="mono">
                            {{ ($prices[$product->id] ?? null)
                                ? '₹'.number_format((float) $prices[$product->id]->on_road_price)
                                : '—' }}
                        </td>
                    @endforeach
                </tr>

                @foreach ($matrix as $group)
                    <tr class="kj-group-row">
                        <th colspan="{{ $products->count() + 1 }}" class="kj-label">{{ $group['group'] }}</th>
                    </tr>
                    @foreach ($group['rows'] as $row)
                        <tr class="{{ $row['differs'] ? 'kj-diff' : '' }}">
                            <td class="text-muted-2">{{ $row['label'] }}</td>
                            @foreach ($row['values'] as $value)
                                <td class="mono">{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach

                <tr>
                    <td></td>
                    @foreach ($products as $product)
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary js-compare-remove"
                                    data-product-id="{{ $product->id }}">{{ __('Remove') }}</button>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="d-flex gap-2 mt-3 flex-wrap">
        <a href="{{ url('/tractors') }}" class="btn btn-outline-primary btn-sm">{{ __('Add another model') }}</a>
        <button type="button" class="btn btn-outline-primary btn-sm js-compare-clear">{{ __('Clear comparison') }}</button>
    </div>

    <section class="kj-panel p-4 mt-5">
        <h2 class="h6">{{ __(':names — which should you buy?', ['names' => $names->implode(' vs ')]) }}</h2>
        <p class="small text-muted-2 mb-0">
            {{ __('Prices here are ex-showroom and change by state. Open a model to see the on-road price for your city and to calculate an EMI before you decide.') }}
        </p>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalog.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initCompare(); });</script>
@endpush
