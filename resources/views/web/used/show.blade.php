@extends('layouts.app')

@section('title', $listing->title.' — ₹'.number_format((float) $listing->expected_price).' | Krishi Junction')
@section('meta_description', __('Used :title in :city — :hours hours, :condition condition. Photos, specifications and verified seller contact.', [
    'title' => $listing->title,
    'city' => $listing->city?->name ?? $listing->district?->name,
    'hours' => number_format((int) $listing->engine_hours),
    'condition' => $listing->condition,
]))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('used.index') }}">{{ __('Used') }}</a></li>
            <li class="breadcrumb-item active">{{ $listing->title }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="kj-panel p-2">
                <div class="ratio kj-thumb rounded" style="--bs-aspect-ratio: 68%;">
                    @if ($listing->primary_image)
                        <img id="listing-main-image" src="{{ $listing->primary_image->url() }}"
                             alt="{{ $listing->title }}" class="object-fit-cover rounded">
                    @else
                        <span class="d-flex align-items-center justify-content-center">@include('partials.machine-icon')</span>
                    @endif
                </div>

                @if ($listing->images->count() > 1)
                    <div class="d-flex gap-2 mt-2 overflow-auto">
                        @foreach ($listing->images as $image)
                            <button type="button" class="btn p-0 border rounded js-listing-thumb"
                                    data-full="{{ $image->url() }}" title="{{ ucfirst($image->angle) }}">
                                <img src="{{ $image->thumbnailUrl() }}" alt="{{ ucfirst($image->angle) }}"
                                     width="72" height="54" loading="lazy" class="object-fit-cover rounded">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <section class="kj-panel p-4 mt-4">
                <h2 class="h6 mb-3">{{ __('Condition & usage') }}</h2>
                <div class="row g-3">
                    @foreach ([
                        __('Year') => $listing->manufacturing_year,
                        __('Engine hours') => $listing->engine_hours ? number_format($listing->engine_hours) : '—',
                        __('Condition') => ucfirst(str_replace('_', ' ', (string) $listing->condition)),
                        __('Front tyres') => ucfirst((string) $listing->tyre_condition_front ?: '—'),
                        __('Rear tyres') => ucfirst((string) $listing->tyre_condition_rear ?: '—'),
                        __('RC available') => $listing->has_rc ? __('Yes') : __('No'),
                        __('Insurance') => $listing->has_insurance ? __('Valid') : __('Not valid'),
                        __('Loan running') => $listing->is_financed ? __('Yes') : __('No'),
                    ] as $label => $value)
                        <div class="col-6 col-md-3">
                            <div class="kj-stat">
                                <div class="k">{{ $label }}</div>
                                <div class="v" style="font-size:1rem;">{{ $value }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($listing->description)
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h6 mb-2">{{ __('Seller\'s description') }}</h2>
                    <p class="small mb-0">{{ $listing->description }}</p>
                </section>
            @endif

            @if ($keySpecs->isNotEmpty())
                <section class="kj-panel p-4 mt-4">
                    <h2 class="h6 mb-1">{{ __('Specifications') }}</h2>
                    <p class="small text-muted-2 mb-3">
                        {{ __('From the catalogue entry for the :model.', ['model' => $listing->product?->full_name]) }}
                    </p>
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($keySpecs as $spec)
                                <tr>
                                    <td class="text-muted-2" style="width:45%">{{ $spec->attribute->name }}</td>
                                    <td class="mono">{{ $spec->value }} {{ $spec->attribute->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="d-flex gap-2 flex-wrap mb-2">
                @if ($listing->is_verified)
                    <span class="badge badge-ok">{{ __('Inspected by Krishi Junction') }}</span>
                @endif
                <span class="badge badge-muted">{{ ucfirst($listing->seller_type) }}</span>
                <span class="badge badge-info mono">{{ $listing->reference_no }}</span>
            </div>

            <h1 class="h4 mb-1">{{ $listing->title }}</h1>
            <p class="text-muted-2 small">
                {{ $listing->city?->name ?? $listing->district?->name }}, {{ $listing->state?->name }}
                · {{ __('listed :when', ['when' => $listing->published_at?->diffForHumans()]) }}
                · {{ trans_choice(':count view|:count views', $listing->view_count, ['count' => $listing->view_count]) }}
            </p>

            <div class="my-3">
                <div class="kj-label">{{ __('Asking price') }}</div>
                <div class="h3 mb-0" style="font-family:Archivo,sans-serif;">
                    ₹{{ number_format((float) $listing->expected_price) }}
                </div>
                @if ($listing->is_price_negotiable)
                    <span class="small text-muted-2">{{ __('Negotiable') }}</span>
                @endif
            </div>

            @if ($valuation)
                <div class="kj-panel p-3 mb-3">
                    <div class="kj-label mb-1">{{ __('Fair-price estimate') }}</div>
                    <div class="fw-semibold mono">
                        ₹{{ number_format($valuation['min']) }} – ₹{{ number_format($valuation['max']) }}
                    </div>
                    <p class="small text-muted-2 mb-0 mt-1">
                        {{ __('Estimated from the model\'s ex-showroom price, age, hours, condition and region. An estimate, not a valuation.') }}
                    </p>
                </div>
            @endif

            <div class="kj-panel p-3" id="contact-box">
                <div class="kj-label mb-2">{{ __('Contact the seller') }}</div>

                <div id="reveal-form">
                    <p class="small text-muted-2">
                        {{ __('Verify your mobile to see the number. The seller is told you are interested.') }}
                    </p>

                    <label class="form-label" for="lead-name">{{ __('Your name') }}</label>
                    <input type="text" id="lead-name" class="form-control mb-2" maxlength="100" required>

                    <label class="form-label" for="lead-mobile">{{ __('Your mobile') }}</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text mono">+91</span>
                        <input type="tel" id="lead-mobile" class="form-control mono" maxlength="10"
                               inputmode="numeric" autocomplete="tel-national" required>
                    </div>

                    <div id="lead-otp-wrap" class="d-none">
                        <label class="form-label" for="lead-otp">{{ __('Enter the code') }}</label>
                        <input type="text" id="lead-otp" class="form-control kj-otp-input mb-2"
                               maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                    </div>

                    <button type="button" class="btn btn-primary w-100" id="lead-send-otp">{{ __('Send code') }}</button>
                    <button type="button" class="btn btn-primary w-100 d-none" id="lead-verify">{{ __('Show number') }}</button>
                </div>

                <div id="reveal-result" class="d-none">
                    <p class="small text-muted-2 mb-1">{{ __('Seller') }}</p>
                    <p class="h5 mono mb-1" id="seller-mobile"></p>
                    <p class="small text-muted-2 mb-0" id="seller-name"></p>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#reportModal">{{ __('Report this listing') }}</button>
            </div>
        </div>
    </div>

    @if ($similar->isNotEmpty())
        <section class="mt-5">
            <h2 class="h5 mb-3">{{ __('Similar used machinery') }}</h2>
            <div class="row g-3">
                @foreach ($similar as $other)
                    <div class="col-6 col-md-3"><x-used-card :listing="$other" /></div>
                @endforeach
            </div>
        </section>
    @endif
</div>

<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h6">{{ __('Report this listing') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="report-reason">{{ __('What is wrong?') }}</label>
                <select id="report-reason" class="form-select mb-3">
                    @foreach ([
                        'sold' => __('Already sold'), 'fake' => __('Looks fake'),
                        'wrong_price' => __('Price is wrong'), 'spam' => __('Spam'),
                        'abusive' => __('Abusive content'), 'duplicate' => __('Duplicate listing'),
                        'other' => __('Something else'),
                    ] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="report-details">{{ __('Details (optional)') }}</label>
                <textarea id="report-details" class="form-control" rows="3" maxlength="1000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="report-submit">{{ __('Send report') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/marketplace.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>
$(function () {
    KJ.initListingDetail({
        otpUrl: '{{ route('ajax.leads.otp') }}',
        revealUrl: '{{ route('ajax.used.reveal', $listing) }}',
        reportUrl: '{{ route('ajax.used.report', $listing) }}',
    });
});
</script>
@endpush
