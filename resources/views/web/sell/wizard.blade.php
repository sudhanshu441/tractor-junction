@extends('layouts.app')

@section('title', __('Sell your tractor — free listing | Krishi Junction'))
@section('meta_description', __('List your used tractor or implement in under three minutes. Free, verified buyers, no commission.'))

@section('content')
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h4 mb-1">{{ __('Sell your machinery') }}</h1>
            <p class="text-muted-2 small mb-4">
                {{ __('Six short steps. Everything is saved as you go, so you can stop and come back.') }}
            </p>

            {{-- progress --}}
            <div class="kj-steps mb-4" role="list">
                @foreach ([__('Type'), __('Machine'), __('Condition'), __('Photos'), __('Price'), __('Confirm')] as $i => $label)
                    <div class="kj-step {{ $i === 0 ? 'is-current' : '' }}" data-step="{{ $i + 1 }}" role="listitem">
                        <span class="kj-step-dot">{{ $i + 1 }}</span>
                        <span class="kj-step-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <div class="kj-panel p-4">
                {{-- Step 1: category --}}
                <section class="kj-wizard-step" data-step="1">
                    <h2 class="h6 mb-3">{{ __('What are you selling?') }}</h2>
                    <div class="row g-2">
                        @foreach ($categories as $category)
                            <div class="col-6 col-md-4">
                                <button type="button" class="btn btn-outline-primary w-100 py-3 js-pick-category"
                                        data-id="{{ $category->id }}" data-type="{{ $category->type }}">
                                    {{ $category->name }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Step 2: brand, model, year --}}
                <section class="kj-wizard-step d-none" data-step="2">
                    <h2 class="h6 mb-3">{{ __('Which machine is it?') }}</h2>

                    <label class="form-label" for="brand_id">{{ __('Brand') }}</label>
                    <select id="brand_id" class="form-select mb-3" required>
                        <option value="">{{ __('Select brand') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>

                    <label class="form-label" for="product_id">{{ __('Model') }}</label>
                    <select id="product_id" class="form-select mb-1" disabled>
                        <option value="">{{ __('Select brand first') }}</option>
                    </select>
                    <p class="form-text">{{ __('Cannot find it? Leave this blank and describe it later.') }}</p>

                    <label class="form-label mt-2" for="manufacturing_year">{{ __('Year of manufacture') }}</label>
                    <input type="number" id="manufacturing_year" class="form-control mb-3"
                           min="1980" max="{{ date('Y') }}" inputmode="numeric" required>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary js-back">{{ __('Back') }}</button>
                        <button type="button" class="btn btn-primary flex-grow-1 js-next" data-step="2">{{ __('Continue') }}</button>
                    </div>
                </section>

                {{-- Step 3: condition --}}
                <section class="kj-wizard-step d-none" data-step="3">
                    <h2 class="h6 mb-1">{{ __('How has it been used?') }}</h2>
                    <p class="small text-muted-2 mb-3">{{ __('Honest answers get better offers — buyers see the machine anyway.') }}</p>

                    <label class="form-label" for="engine_hours">{{ __('Engine hours on the meter') }}</label>
                    <input type="number" id="engine_hours" class="form-control mb-3" min="0" max="99999" inputmode="numeric">

                    <span class="form-label d-block">{{ __('Overall condition') }}</span>
                    <div class="d-flex gap-2 flex-wrap mb-3" id="condition-group">
                        @foreach (['excellent' => __('Excellent'), 'good' => __('Good'), 'average' => __('Average'), 'needs_repair' => __('Needs repair')] as $value => $label)
                            <button type="button" class="btn btn-sm btn-outline-primary js-pick-condition"
                                    data-value="{{ $value }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="tyre_front">{{ __('Front tyres') }}</label>
                            <select id="tyre_front" class="form-select">
                                <option value="">—</option>
                                @foreach (['new' => __('New'), 'good' => __('Good'), 'worn' => __('Worn')] as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="tyre_rear">{{ __('Rear tyres') }}</label>
                            <select id="tyre_rear" class="form-select">
                                <option value="">—</option>
                                @foreach (['new' => __('New'), 'good' => __('Good'), 'worn' => __('Worn')] as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-check"><input class="form-check-input" type="checkbox" id="has_rc" checked>
                        <label class="form-check-label" for="has_rc">{{ __('RC available') }}</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="has_insurance">
                        <label class="form-check-label" for="has_insurance">{{ __('Insurance valid') }}</label></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="is_financed">
                        <label class="form-check-label" for="is_financed">{{ __('Loan still running on it') }}</label></div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary js-back">{{ __('Back') }}</button>
                        <button type="button" class="btn btn-primary flex-grow-1 js-next" data-step="3">{{ __('Continue to photos') }}</button>
                    </div>
                </section>

                {{-- Step 4: photos --}}
                <section class="kj-wizard-step d-none" data-step="4">
                    <h2 class="h6 mb-1">{{ __('Add photos') }}</h2>
                    <p class="small text-muted-2 mb-3">
                        {{ __('At least :min, up to :max. Take them in daylight — listings with clear photos sell faster.', ['min' => $minPhotos, 'max' => $maxPhotos]) }}
                    </p>

                    <div class="row g-2 mb-3" id="photo-grid"></div>

                    <label class="form-label" for="photo-angle">{{ __('What does this photo show?') }}</label>
                    <select id="photo-angle" class="form-select mb-2">
                        @foreach (['front' => __('Front'), 'rear' => __('Rear'), 'left' => __('Left side'), 'right' => __('Right side'),
                                   'engine' => __('Engine'), 'meter' => __('Hour meter'), 'tyre' => __('Tyres'),
                                   'document' => __('Documents'), 'other' => __('Other')] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>

                    <input type="file" id="photo-input" class="form-control mb-2" accept="image/*" capture="environment">
                    <p class="form-text" id="photo-count">{{ __('0 photos added') }}</p>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary js-back">{{ __('Back') }}</button>
                        <button type="button" class="btn btn-primary flex-grow-1 js-next" data-step="4">{{ __('Continue') }}</button>
                    </div>
                </section>

                {{-- Step 5: price and location --}}
                <section class="kj-wizard-step d-none" data-step="5">
                    <h2 class="h6 mb-3">{{ __('Price and location') }}</h2>

                    <label class="form-label" for="expected_price">{{ __('Your asking price (₹)') }}</label>
                    <input type="number" id="expected_price" class="form-control mb-1" min="5000" inputmode="numeric" required>
                    <div id="valuation-hint" class="form-text"></div>

                    <div class="form-check my-3">
                        <input class="form-check-input" type="checkbox" id="is_price_negotiable" checked>
                        <label class="form-check-label" for="is_price_negotiable">{{ __('Price is negotiable') }}</label>
                    </div>

                    <div class="js-geo row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="state_id">{{ __('State') }}</label>
                            <select id="state_id" class="form-select js-state" required>
                                <option value="">{{ __('Select state') }}</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="district_id">{{ __('District') }}</label>
                            <select id="district_id" class="form-select js-district" disabled required>
                                <option value="">{{ __('Select state first') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="city_id">{{ __('City') }}</label>
                            <select id="city_id" class="form-select js-city" disabled>
                                <option value="">{{ __('Select district first') }}</option>
                            </select>
                        </div>
                    </div>

                    <label class="form-label" for="description">{{ __('Anything else buyers should know?') }}</label>
                    <textarea id="description" class="form-control mb-3" rows="3" maxlength="2000"></textarea>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary js-back">{{ __('Back') }}</button>
                        <button type="button" class="btn btn-primary flex-grow-1 js-next" data-step="5">{{ __('Continue') }}</button>
                    </div>
                </section>

                {{-- Step 6: verify and submit --}}
                <section class="kj-wizard-step d-none" data-step="6">
                    <h2 class="h6 mb-1">{{ __('Confirm your number') }}</h2>
                    <p class="small text-muted-2 mb-3">
                        {{ __('Buyers only see it after they verify their own number, so you will not get spam calls.') }}
                    </p>

                    <label class="form-label" for="seller_name">{{ __('Your name') }}</label>
                    <input type="text" id="seller_name" class="form-control mb-3" maxlength="100"
                           value="{{ auth()->user()?->name }}" required>

                    <label class="form-label" for="seller_mobile">{{ __('Your mobile') }}</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text mono">+91</span>
                        <input type="tel" id="seller_mobile" class="form-control mono" maxlength="10"
                               inputmode="numeric" value="{{ auth()->user()?->mobile }}" required>
                    </div>

                    <div id="seller-otp-wrap" class="d-none mb-3">
                        <label class="form-label" for="seller_otp">{{ __('Enter the code') }}</label>
                        <input type="text" id="seller_otp" class="form-control kj-otp-input"
                               maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary js-back">{{ __('Back') }}</button>
                        <button type="button" class="btn btn-primary flex-grow-1" id="seller-send-otp">{{ __('Send code') }}</button>
                        <button type="button" class="btn btn-primary flex-grow-1 d-none" id="seller-submit">{{ __('Submit listing') }}</button>
                    </div>
                </section>
            </div>

            <p class="small text-muted-2 mt-3 text-center" id="draft-status">
                {{ __('Free to list. Reviewed within :hours working hours.', ['hours' => config('kj.listings.moderation_sla_hours')]) }}
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/marketplace.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>
$(function () {
    KJ.initSellWizard({
        stepUrl: '{{ url('/ajax/sell/step') }}',
        modelsUrl: '{{ route('ajax.sell.models') }}',
        photoUrl: '{{ route('ajax.sell.photo') }}',
        submitUrl: '{{ route('ajax.sell.submit') }}',
        otpUrl: '{{ route('ajax.leads.otp') }}',
        minPhotos: {{ $minPhotos }},
        maxPhotos: {{ $maxPhotos }},
    });
});
</script>
@endpush
