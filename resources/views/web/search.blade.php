@extends('layouts.app')

@section('title', $term ? __('Search: :term | Krishi Junction', ['term' => $term]) : __('Search | Krishi Junction'))
@section('meta_description', __('Search tractors, implements and harvesters by brand, model or horsepower.'))

@section('content')
<div class="container-xl py-4">
    <h1 class="h4 mb-3">
        @if ($term)
            {{ __('Results for ":term"', ['term' => $term]) }}
        @else
            {{ __('Search') }}
        @endif
    </h1>

    <form method="GET" action="{{ route('search') }}" class="kj-panel p-3 mb-4" role="search">
        <div class="input-group">
            <input type="search" name="q" value="{{ $term }}" class="form-control"
                   placeholder="{{ __('Try "Mahindra 575" or "rotavator"') }}"
                   aria-label="{{ __('Search machinery') }}" autocomplete="off">
            <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
        </div>
    </form>

    @if ($term)
        <p class="text-muted-2 small">
            {{ trans_choice(':count result|:count results', $products->count(), ['count' => $products->count()]) }}
        </p>
    @endif

    <div class="row g-3">
        @forelse ($products as $product)
            <div class="col-6 col-md-4 col-xl-3">
                <x-product-card :product="$product" :compare-ids="[]" />
            </div>
        @empty
            <div class="col-12">
                <div class="kj-panel p-5 text-center">
                    @if ($term)
                        <p class="fw-semibold mb-1">{{ __('Nothing matched ":term"', ['term' => $term]) }}</p>
                        <p class="small text-muted-2 mb-0">{{ __('Check the spelling, or try just the brand name.') }}</p>
                    @else
                        <p class="small text-muted-2 mb-0">{{ __('Enter a brand, model or type of machinery.') }}</p>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($trending->isNotEmpty())
        <section class="mt-5">
            <h2 class="h6 mb-2">{{ __('Trending searches') }}</h2>
            <div class="d-flex gap-2 flex-wrap">
                @foreach ($trending as $row)
                    <a href="{{ route('search', ['q' => $row['term']]) }}" class="btn btn-sm btn-outline-primary">{{ $row['term'] }}</a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
