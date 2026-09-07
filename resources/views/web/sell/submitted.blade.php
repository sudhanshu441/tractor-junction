@extends('layouts.app')

@section('title', __('Listing submitted | Krishi Junction'))

@section('content')
<div class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="kj-panel p-5">
                <span class="badge badge-ok mb-3">{{ __('Submitted') }}</span>
                <h1 class="h4 mb-2">{{ __('Your listing is with our team') }}</h1>
                <p class="text-muted-2">
                    {{ __('Reference :reference. We check every listing by hand and will confirm within :hours working hours.', [
                        'reference' => $listing->reference_no,
                        'hours' => $slaHours,
                    ]) }}
                </p>

                @if ($valuation)
                    <div class="kj-panel p-3 my-4 text-start">
                        <div class="kj-label mb-1">{{ __('What similar machines sell for') }}</div>
                        <div class="fw-semibold mono">
                            ₹{{ number_format($valuation['min']) }} – ₹{{ number_format($valuation['max']) }}
                        </div>
                        <p class="small text-muted-2 mb-0 mt-1">
                            {{ __('Your asking price is ₹:price. Buyers compare against this band.', [
                                'price' => number_format((float) $listing->expected_price),
                            ]) }}
                        </p>
                    </div>
                @endif

                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="{{ route('account.listings.index') }}" class="btn btn-primary">{{ __('See my listings') }}</a>
                    <a href="{{ route('used.index') }}" class="btn btn-outline-primary">{{ __('Browse used machinery') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
