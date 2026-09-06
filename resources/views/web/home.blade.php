@extends('layouts.app')

@section('title', __('New & Used Tractors in India — Price, Specs | Krishi Junction'))

@section('content')
<section class="kj-tint border-bottom">
    <div class="container-xl py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="kj-eyebrow">{{ __("India's rural machinery marketplace") }}</div>
                <h1 class="display-6 mt-2 mb-2">{{ __('Find the right tractor, at the right price, near you.') }}</h1>
                <p class="text-muted-2 mb-4" style="max-width: 56ch;">
                    {{ __('Compare new tractors and implements, buy and sell used machinery, check EMI, and reach verified dealers in your district.') }}
                </p>

                <form class="kj-panel p-3 js-geo" method="GET" action="#">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="home-state">{{ __('State') }}</label>
                            <select id="home-state" name="state" class="form-select js-state">
                                <option value="">{{ __('Select state') }}</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="home-district">{{ __('District') }}</label>
                            <select id="home-district" name="district" class="form-select js-district" disabled>
                                <option value="">{{ __('Select state first') }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="home-city">{{ __('City') }}</label>
                            <select id="home-city" name="city" class="form-select js-city" disabled>
                                <option value="">{{ __('Select district first') }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button type="submit" class="btn btn-primary w-100">{{ __('Find machinery') }}</button>
                        </div>
                    </div>
                    <p class="small text-muted-2 mb-0 mt-2">
                        {{ __('Dependent selects load over AJAX from the geography master.') }}
                    </p>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="kj-panel p-4">
                    <div class="kj-label mb-3">{{ __('Platform status') }}</div>
                    <div class="row g-2">
                        @foreach ([
                            __('States') => \App\Models\State::count(),
                            __('Districts') => \App\Models\District::count(),
                            __('Brands') => \App\Models\Brand::count(),
                            __('Registered users') => \App\Models\User::count(),
                        ] as $label => $value)
                            <div class="col-6">
                                <div class="kj-stat">
                                    <div class="k">{{ $label }}</div>
                                    <div class="v">{{ number_format($value) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container-xl py-5">
    <h2 class="h5 mb-1">{{ __('Browse by brand') }}</h2>
    <p class="text-muted-2 small mb-3">{{ __('Managed from the admin panel.') }}</p>

    @if ($brands->isEmpty())
        <div class="kj-panel p-4 text-center">
            <p class="mb-1 fw-semibold">{{ __('No brands yet') }}</p>
            <p class="text-muted-2 small mb-0">
                {{ __('Brands, models, specifications and prices arrive in Phase 2 of the build.') }}
            </p>
        </div>
    @else
        <div class="row g-3">
            @foreach ($brands as $brand)
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="#" class="card h-100 text-decoration-none text-center p-3">
                        <div class="fw-semibold small">{{ $brand->name }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
