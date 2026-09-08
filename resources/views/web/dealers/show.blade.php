@extends('layouts.app')

@section('title', $dealer->display_name.' — '.__(':brand tractor dealer in :city | Krishi Junction', [
    'brand' => $dealer->brands->first()?->name ?? '',
    'city' => $dealer->city?->name ?? $dealer->state?->name,
]))
@section('meta_description', \Illuminate\Support\Str::limit($dealer->about
    ?: __(':name is a verified tractor dealer in :city.', ['name' => $dealer->display_name, 'city' => $dealer->city?->name]), 160))

@push('styles')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $dealer->display_name,
    'telephone' => $dealer->mobile,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $dealer->address,
        'addressLocality' => $dealer->city?->name,
        'addressRegion' => $dealer->state?->name,
        'postalCode' => $dealer->pincode,
        'addressCountry' => 'IN',
    ],
    'aggregateRating' => $dealer->rating_count > 0 ? [
        '@type' => 'AggregateRating',
        'ratingValue' => (float) $dealer->rating_avg,
        'reviewCount' => $dealer->rating_count,
    ] : null,
], JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dealers.index') }}">{{ __('Dealers') }}</a></li>
            @if ($dealer->state)
                <li class="breadcrumb-item"><a href="{{ route('dealers.state', $dealer->state->slug) }}">{{ $dealer->state->name }}</a></li>
            @endif
            <li class="breadcrumb-item active">{{ $dealer->display_name }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="kj-panel p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h1 class="h4 mb-1">{{ $dealer->display_name }}</h1>
                        <p class="text-muted-2 small mb-0">
                            {{ ucfirst(str_replace('_', ' ', $dealer->dealer_type)) }} ·
                            <span class="mono">{{ $dealer->code }}</span>
                        </p>
                    </div>
                    <span class="badge badge-ok">{{ __('Verified dealer') }}</span>
                </div>

                @if ($dealer->brands->isNotEmpty())
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        @foreach ($dealer->brands as $brand)
                            <a href="{{ url('/tractors/brand/'.$brand->slug) }}" class="badge badge-muted text-decoration-none">
                                {{ $brand->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($dealer->about)
                    <p class="small mt-3 mb-0">{{ $dealer->about }}</p>
                @endif
            </div>

            @if ($dealer->branches->isNotEmpty())
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h6 mb-3">{{ __('Branches') }}</h2>
                    @foreach ($dealer->branches as $branch)
                        <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                            <div>
                                <b class="small">{{ $branch->name }}</b>
                                @if ($branch->is_head_office)<span class="badge badge-muted ms-1">{{ __('Head office') }}</span>@endif
                                <div class="small text-muted-2">{{ $branch->address }}, {{ $branch->city?->name }}</div>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            @if ($dealer->inventory->isNotEmpty())
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h6 mb-3">{{ __('New machinery in stock') }}</h2>
                    <div class="row g-2">
                        @foreach ($dealer->inventory->take(9) as $item)
                            @continue(! $item->product)
                            <div class="col-md-4">
                                <div class="card h-100"><div class="card-body p-3">
                                    <div class="small text-muted-2 mono">{{ $item->product->brand?->name }}</div>
                                    <b class="small d-block">{{ $item->product->name }}</b>
                                    <span class="badge {{ $item->availability === 'in_stock' ? 'badge-ok' : 'badge-muted' }} mt-1">
                                        {{ str_replace('_', ' ', $item->availability) }}
                                    </span>
                                </div></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($liveListings->isNotEmpty())
                <section class="mt-4">
                    <h2 class="h6 mb-3">{{ __('Used machinery from this dealer') }}</h2>
                    <div class="row g-3">
                        @foreach ($liveListings as $listing)
                            <div class="col-6 col-md-3"><x-used-card :listing="$listing" /></div>
                        @endforeach
                    </div>
                </section>
            @endif

            @include('partials.reviews', [
                'subject' => $dealer,
                'subjectType' => 'dealer',
                'reviewAspects' => \App\Domain\Engagement\Services\ReviewService::ASPECTS['dealer'],
            ])
        </div>

        <div class="col-lg-4">
            <div class="kj-panel p-4">
                <h2 class="h6 mb-2">{{ __('Contact this dealer') }}</h2>
                <p class="small text-muted-2">
                    {{ __('Leave your number and they will call you. We do not share it with anyone else.') }}
                </p>

                <div id="dealer-lead-form">
                    <label class="form-label" for="lead-name">{{ __('Your name') }}</label>
                    <input type="text" id="lead-name" class="form-control mb-2" maxlength="100" required>

                    <label class="form-label" for="lead-mobile">{{ __('Your mobile') }}</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text mono">+91</span>
                        <input type="tel" id="lead-mobile" class="form-control mono" maxlength="10" inputmode="numeric" required>
                    </div>

                    <div id="lead-otp-wrap" class="d-none mb-2">
                        <label class="form-label" for="lead-otp">{{ __('Enter the code') }}</label>
                        <input type="text" id="lead-otp" class="form-control kj-otp-input" maxlength="6" inputmode="numeric">
                    </div>

                    <label class="form-label" for="lead-message">{{ __('What are you looking for?') }}</label>
                    <textarea id="lead-message" class="form-control mb-2" rows="2" maxlength="1000"></textarea>

                    <button type="button" class="btn btn-primary w-100" id="dealer-send-otp">{{ __('Send code') }}</button>
                    <button type="button" class="btn btn-primary w-100 d-none" id="dealer-submit">{{ __('Send enquiry') }}</button>
                </div>

                <div id="dealer-lead-done" class="d-none">
                    <div class="alert alert-secondary small mb-0" id="dealer-lead-message"></div>
                </div>
            </div>

            <div class="kj-panel p-4 mt-3">
                <h2 class="h6 mb-2">{{ __('Address') }}</h2>
                <p class="small mb-1">{{ $dealer->address }}</p>
                <p class="small text-muted-2 mb-0">
                    {{ $dealer->city?->name }}, {{ $dealer->district?->name }}, {{ $dealer->state?->name }}
                    @if ($dealer->pincode) — <span class="mono">{{ $dealer->pincode }}</span>@endif
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/marketplace.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>
$(function () {
    KJ.initDealerEnquiry({
        otpUrl: '{{ route('ajax.leads.otp') }}',
        leadUrl: '{{ route('ajax.leads.store') }}',
        dealerId: {{ $dealer->id }},
    });
});
</script>
@endpush
