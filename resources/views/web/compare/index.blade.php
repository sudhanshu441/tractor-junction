@extends('layouts.app')

@section('title', __('Compare Tractors — Price, Specs & Mileage | Krishi Junction'))

@section('content')
<div class="container-xl py-4">
    <h1 class="h4 mb-1">{{ __('Compare tractors') }}</h1>
    <p class="text-muted-2 small mb-4">{{ __('Pick two to four models to see their specifications side by side.') }}</p>

    @if ($products->isNotEmpty())
        <div class="kj-panel p-3 mb-4">
            <div class="kj-label mb-2">{{ __('In your comparison') }}</div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach ($products as $product)
                    <span class="badge badge-ok d-inline-flex align-items-center gap-2">
                        {{ $product->full_name }}
                        <button type="button" class="btn-close" style="font-size:.5rem"
                                data-product-id="{{ $product->id }}"
                                aria-label="{{ __('Remove') }}"></button>
                    </span>
                @endforeach
            </div>
            <p class="small text-muted-2 mt-2 mb-0">{{ __('Add one more model to start comparing.') }}</p>
        </div>
    @endif

    <h2 class="h6 mb-3">{{ __('Popular models to compare') }}</h2>
    <div class="row g-3">
        @forelse ($popular as $product)
            <div class="col-6 col-md-3">
                <x-product-card :product="$product" :compare-ids="$products->pluck('id')->all()" />
            </div>
        @empty
            <div class="col-12">
                <div class="kj-panel p-4 text-center">
                    <p class="mb-0 small text-muted-2">{{ __('No models are published yet.') }}</p>
                </div>
            </div>
        @endforelse
    </div>
</div>

<div id="compare-bar-host">
    @include('partials.ajax.compare-bar', ['products' => $products])
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalog.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initCompare(); });</script>
@endpush
