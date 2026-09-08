@extends('layouts.app')

@section('title', __('Apply for a tractor loan | Krishi Junction'))
@section('meta_description', __('Apply for a tractor or implement loan. Four short steps, documents uploaded securely, and a status you can track.'))

@section('content')
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h4 mb-1">{{ __('Apply for a loan') }}</h1>
            <p class="text-muted-2 small mb-4">
                {{ __('Four steps. Everything is saved as you go, and your documents are stored privately.') }}
            </p>

            @if ($financeable)
                <div class="alert alert-secondary small">
                    <b>{{ __('Financing:') }}</b>
                    {{ $financeable instanceof \App\Models\Product ? $financeable->full_name : $financeable->title }}
                </div>
            @endif

            <div class="kj-steps mb-4" role="list">
                @foreach ([__('About you'), __('Machinery'), __('Income'), __('Documents'), __('Confirm')] as $i => $label)
                    <div class="kj-step {{ $i === 0 ? 'is-current' : '' }}" data-step="{{ $i + 1 }}" role="listitem">
                        <span class="kj-step-dot">{{ $i + 1 }}</span>
                        <span class="kj-step-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <form id="loan-form" onsubmit="return false;">
                <div class="kj-panel p-4">
                    {{-- Step 1 --}}
                    <section class="kj-wizard-step" data-step="1">
                        <h2 class="h6 mb-3">{{ __('About you') }}</h2>

                        <div class="kj-panel p-3 mb-3 kj-wash">
                            <div class="kj-label mb-2">{{ __('Quick eligibility check (optional)') }}</div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="number" id="annual_income_quick" class="form-control form-control-sm"
                                           placeholder="{{ __('Annual income ₹') }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" id="machinery_price_quick" class="form-control form-control-sm"
                                           placeholder="{{ __('Machinery price ₹') }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" id="down_payment_quick" class="form-control form-control-sm"
                                           placeholder="{{ __('Down payment ₹') }}">
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="check-eligibility">
                                {{ __('Check before applying') }}
                            </button>
                            <div id="eligibility-result" class="mt-2"></div>
                        </div>

                        <label class="form-label" for="applicant_name">{{ __('Full name') }}</label>
                        <input type="text" id="applicant_name" name="applicant_name" class="form-control mb-3"
                               maxlength="100" value="{{ auth()->user()?->name }}" required>

                        <label class="form-label" for="mobile">{{ __('Mobile') }}</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text mono">+91</span>
                            <input type="tel" id="mobile" name="mobile" class="form-control mono" maxlength="10"
                                   inputmode="numeric" value="{{ auth()->user()?->mobile }}" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="email">{{ __('Email (optional)') }}</label>
                                <input type="email" id="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="date_of_birth">{{ __('Date of birth') }}</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                            </div>
                        </div>

                        <label class="form-label" for="purpose">{{ __('What is the loan for?') }}</label>
                        <select id="purpose" name="purpose" class="form-select mb-3">
                            <option value="new_purchase">{{ __('Buying new machinery') }}</option>
                            <option value="used_purchase">{{ __('Buying used machinery') }}</option>
                            <option value="refinance">{{ __('Refinancing an existing loan') }}</option>
                        </select>

                        <button type="button" class="btn btn-primary w-100 js-loan-next" data-step="1">{{ __('Continue') }}</button>
                    </section>

                    {{-- Step 2 --}}
                    <section class="kj-wizard-step d-none" data-step="2">
                        <h2 class="h6 mb-3">{{ __('Machinery and loan') }}</h2>

                        <label class="form-label" for="machinery_price">{{ __('Machinery price (₹)') }}</label>
                        <input type="number" id="machinery_price" class="form-control mb-3" min="10000" required
                               value="{{ $financeable instanceof \App\Models\Product ? (int) $financeable->price_min : ($financeable->expected_price ?? '') }}">

                        <label class="form-label" for="down_payment2">{{ __('Down payment (₹)') }}</label>
                        <input type="number" id="down_payment2" class="form-control mb-3" min="0" required>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tenure_months">{{ __('Tenure (months)') }}</label>
                                <select id="tenure_months" class="form-select">
                                    @foreach ([12, 24, 36, 48, 60, 72, 84] as $m)
                                        <option value="{{ $m }}" @selected($m === 60)>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expected_interest_rate">{{ __('Expected rate (%)') }}</label>
                                <input type="number" id="expected_interest_rate" class="form-control mono"
                                       step="0.1" min="0" max="36" value="{{ $defaultRate }}">
                            </div>
                        </div>

                        <div class="alert alert-secondary small">
                            {{ __('Indicative EMI') }}: <b id="running-emi">—</b> ·
                            {{ __('Loan amount') }}: <b id="running-loan">—</b>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary js-loan-back">{{ __('Back') }}</button>
                            <button type="button" class="btn btn-primary flex-grow-1 js-loan-next" data-step="2">{{ __('Continue') }}</button>
                        </div>
                    </section>

                    {{-- Step 3 --}}
                    <section class="kj-wizard-step d-none" data-step="3">
                        <h2 class="h6 mb-1">{{ __('Income and land') }}</h2>
                        <p class="small text-muted-2 mb-3">{{ __('Used for eligibility only. Never shared with dealers.') }}</p>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="annual_income">{{ __('Annual income (₹)') }}</label>
                                <input type="number" id="annual_income" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="income_source">{{ __('Main income from') }}</label>
                                <select id="income_source" class="form-select">
                                    @foreach (['farming' => __('Farming'), 'business' => __('Business'), 'salary' => __('Salary'), 'other' => __('Other')] as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="form-label" for="land_holding_acres">{{ __('Land holding (acres)') }}</label>
                        <input type="number" id="land_holding_acres" class="form-control mb-3" step="0.1" min="0">

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

                        <label class="form-label" for="address">{{ __('Address') }}</label>
                        <textarea id="address" class="form-control mb-3" rows="2" maxlength="500"></textarea>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="pan">{{ __('PAN') }}</label>
                                <input type="text" id="pan" class="form-control mono" maxlength="12" placeholder="ABCDE1234F">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="aadhaar">{{ __('Aadhaar') }}</label>
                                <input type="text" id="aadhaar" class="form-control mono" maxlength="14" placeholder="XXXX XXXX 1234">
                            </div>
                        </div>
                        <p class="form-text">
                            {{ __('We store only the last four digits of these. The full number is never saved.') }}
                        </p>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary js-loan-back">{{ __('Back') }}</button>
                            <button type="button" class="btn btn-primary flex-grow-1 js-loan-next" data-step="3">{{ __('Continue to documents') }}</button>
                        </div>
                    </section>

                    {{-- Step 4 --}}
                    <section class="kj-wizard-step d-none" data-step="4">
                        <h2 class="h6 mb-1">{{ __('Documents') }}</h2>
                        <p class="small text-muted-2 mb-3">
                            {{ __('PDF or photo, up to :mb MB each. Stored privately and opened only through a link that expires in minutes.', ['mb' => round($maxKb / 1024)]) }}
                        </p>

                        <div class="d-flex gap-2 flex-wrap mb-3">
                            @foreach ($requiredDocuments as $doc)
                                <span class="badge badge-muted" id="doc-{{ $doc }}">
                                    {{ ucfirst(str_replace('_', ' ', $doc)) }}
                                </span>
                            @endforeach
                        </div>

                        <label class="form-label" for="doc-type">{{ __('Which document is this?') }}</label>
                        <select id="doc-type" class="form-select mb-2">
                            @foreach (['aadhaar' => __('Aadhaar'), 'pan' => __('PAN card'), 'land_record' => __('Land record'),
                                       'bank_statement' => __('Bank statement'), 'income_proof' => __('Income proof'),
                                       'quotation' => __('Dealer quotation'), 'photo' => __('Photograph'), 'other' => __('Other')] as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>

                        <input type="file" id="document-input" class="form-control mb-2" accept=".pdf,image/*">
                        <p class="form-text" id="doc-status"></p>
                        <div id="missing-docs" class="mb-3"></div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary js-loan-back">{{ __('Back') }}</button>
                            <button type="button" class="btn btn-primary flex-grow-1 js-loan-next" data-step="4">{{ __('Continue') }}</button>
                        </div>
                    </section>

                    {{-- Step 5 --}}
                    <section class="kj-wizard-step d-none" data-step="5">
                        <h2 class="h6 mb-1">{{ __('Confirm your number') }}</h2>
                        <p class="small text-muted-2 mb-3">{{ __('We verify it so we can tell you what happens next.') }}</p>

                        <label class="form-label" for="confirm_name">{{ __('Your name') }}</label>
                        <input type="text" id="confirm_name" class="form-control mb-3" maxlength="100"
                               value="{{ auth()->user()?->name }}" required>

                        <label class="form-label" for="confirm_mobile">{{ __('Your mobile') }}</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text mono">+91</span>
                            <input type="tel" id="confirm_mobile" class="form-control mono" maxlength="10"
                                   inputmode="numeric" value="{{ auth()->user()?->mobile }}" required>
                        </div>

                        <div id="loan-otp-wrap" class="d-none mb-3">
                            <label class="form-label" for="loan_otp">{{ __('Enter the code') }}</label>
                            <input type="text" id="loan_otp" class="form-control kj-otp-input"
                                   maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary js-loan-back">{{ __('Back') }}</button>
                            <button type="button" class="btn btn-primary flex-grow-1" id="loan-send-otp">{{ __('Send code') }}</button>
                            <button type="button" class="btn btn-primary flex-grow-1 d-none" id="loan-submit">{{ __('Submit application') }}</button>
                        </div>
                    </section>
                </div>
            </form>

            <p class="small text-muted-2 mt-3 text-center" id="draft-status">
                {{ __('Krishi Junction introduces you to lenders. We are not a lender and do not decide your loan.') }}
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/finance.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>
$(function () {
    KJ.initLoanWizard({
        stepUrl: '{{ url('/ajax/loan/step') }}',
        eligibilityUrl: '{{ route('ajax.loan.eligibility') }}',
        documentUrl: '{{ route('ajax.loan.document') }}',
        submitUrl: '{{ route('ajax.loan.submit') }}',
        otpUrl: '{{ route('ajax.leads.otp') }}',
    });
});
</script>
@endpush
