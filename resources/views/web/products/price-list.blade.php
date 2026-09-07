@extends('layouts.app')

@section('title', __('Tractor Price List in :state 2026 | Krishi Junction', ['state' => $state->name]))
@section('meta_description', __('On-road tractor prices in :state — ex-showroom, RTO and insurance for every model.', ['state' => $state->name]))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/tractors') }}">{{ __('Tractors') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Price list') }} — {{ $state->name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ __('Tractor price list in :state', ['state' => $state->name]) }}</h1>
            <p class="text-muted-2 small mb-0">{{ __('Ex-showroom and on-road prices, updated by our catalogue team.') }}</p>
        </div>

        <form method="GET" action="{{ route('home') }}" class="d-flex gap-2" onsubmit="return false;">
            <label class="kj-label mb-0 align-self-center" for="state-switch">{{ __('Change state') }}</label>
            <select id="state-switch" class="form-select form-select-sm" style="width:auto;"
                    onchange="if (this.value) window.location = this.value;">
                @foreach ($states as $option)
                    <option value="{{ route('products.price-list', $option->slug) }}" @selected($option->id === $state->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-responsive kj-panel">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Model') }}</th>
                    <th>{{ __('HP') }}</th>
                    <th class="num">{{ __('Ex-showroom') }}</th>
                    <th class="num">{{ __('On-road in :state', ['state' => $state->name]) }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $statePrice = $priceService->resolve($product, $state->id); @endphp
                    <tr>
                        <td>
                            <a href="{{ route('products.show', [$product->brand->slug, $product->slug]) }}"
                               class="text-decoration-none fw-semibold">{{ $product->full_name }}</a>
                        </td>
                        <td class="mono small">{{ $product->hp_label ?? '—' }}</td>
                        <td class="num">{{ \App\Domain\Catalog\Services\PriceService::range($product->price_min, $product->price_max) }}</td>
                        <td class="num">
                            {{ $statePrice ? '₹'.number_format((float) $statePrice->on_road_price) : '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('products.show', [$product->brand->slug, $product->slug]) }}"
                               class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted-2 small py-4">{{ __('No models published yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
@endsection
