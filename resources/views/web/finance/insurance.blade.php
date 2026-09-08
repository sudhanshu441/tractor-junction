@extends('layouts.app')

@section('title', __('Tractor Insurance — Compare Cover & Renew Online | Krishi Junction'))
@section('meta_description', __('Compare tractor insurance from partner insurers. Comprehensive, third-party and own-damage cover, with quotes called back the same working day.'))

@section('content')
<section class="kj-tint border-bottom">
    <div class="container-xl py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="kj-eyebrow">{{ __('Insurance') }}</div>
                <h1 class="display-6 mt-2 mb-2">{{ __('Cover for the machine you cannot farm without.') }}</h1>
                <p class="text-muted-2 mb-0" style="max-width: 56ch;">
                    {{ __('Tell us the tractor and the cover you want. An advisor calls back with quotes from the insurers that write policies in your state — no obligation, and your number goes to nobody else.') }}
                </p>
            </div>
            <div class="col-lg-5">
                <div class="kj-panel p-4">
                    <div class="kj-label mb-2">{{ __('Third-party cover is compulsory') }}</div>
                    <p class="small mb-0">
                        {{ __('Every tractor used on a public road must carry at least third-party insurance under the Motor Vehicles Act. Comprehensive cover adds your own machine, fire, theft and natural calamity.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container-xl py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <h2 class="h5 mb-3">{{ __('What each cover includes') }}</h2>
            <div class="row g-3 mb-4">
                @foreach ([
                    __('Comprehensive') => __('Damage to your own tractor plus third-party liability, fire, theft and natural calamity. Add-ons cover the implement and the driver.'),
                    __('Third party only') => __('The legal minimum. Pays for injury or damage you cause to someone else. Nothing towards your own machine.'),
                    __('Own damage only') => __('Adds your own machine to a third-party policy you already hold elsewhere.'),
                ] as $heading => $text)
                    <div class="col-md-4">
                        <div class="kj-panel p-3 h-100">
                            <div class="kj-label mb-1">{{ $heading }}</div>
                            <p class="small mb-0">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <h2 class="h5 mb-3">{{ __('Partner insurers') }}</h2>

            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6">
                        <label class="form-label" for="state-filter">{{ __('Show insurers writing in') }}</label>
                        <select id="state-filter" name="state_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('All states') }}</option>
                            @foreach ($states as $state)
                                <option value="{{ $state['id'] }}" @selected($selectedState === $state['id'])>{{ $state['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <noscript><button class="btn btn-outline-primary btn-sm" type="submit">{{ __('Filter') }}</button></noscript>
                    </div>
                </div>
            </form>

            <div class="row g-3">
                @forelse ($partners as $partner)
                    <div class="col-md-6">
                        <div class="kj-panel p-3 h-100">
                            <b class="d-block">{{ $partner['name'] }}</b>
                            @if ($partner['description'])
                                <p class="small text-muted-2 mb-2">{{ $partner['description'] }}</p>
                            @endif
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($partner['coverage_types'] as $coverage)
                                    <span class="badge badge-muted">{{ str_replace('_', ' ', $coverage) }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="small text-muted-2">{{ __('No partner insurers listed for that state yet — send the form anyway and an advisor will find cover.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-5">
            <div class="kj-panel p-4" id="insurance-form">
                <h2 class="h6 mb-3">{{ __('Get quotes') }}</h2>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="ins-name">{{ __('Your name') }}</label>
                        <input type="text" id="ins-name" name="name" class="form-control" maxlength="100" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="ins-mobile">{{ __('Mobile number') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <input type="tel" id="ins-mobile" name="mobile" class="form-control" inputmode="numeric"
                                   pattern="[6-9][0-9]{9}" maxlength="10" required>
                            <button type="button" class="btn btn-outline-primary" id="ins-send-otp">{{ __('Send code') }}</button>
                        </div>
                    </div>

                    <div class="col-12" id="ins-otp-row" hidden>
                        <label class="form-label" for="ins-otp">{{ __('Verification code') }}</label>
                        <input type="text" id="ins-otp" name="otp" class="form-control" inputmode="numeric" maxlength="8">
                    </div>

                    <div class="col-sm-7">
                        <label class="form-label" for="ins-coverage">{{ __('Cover wanted') }}</label>
                        <select id="ins-coverage" name="coverage_type" class="form-select">
                            @foreach ($coverageTypes as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-5">
                        <label class="form-label" for="ins-year">{{ __('Year of make') }}</label>
                        <input type="number" id="ins-year" name="manufacturing_year" class="form-control"
                               min="1980" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') - 5 }}">
                    </div>

                    <div class="col-sm-7">
                        <label class="form-label" for="ins-reg">{{ __('Registration number') }}</label>
                        <input type="text" id="ins-reg" name="registration_number" class="form-control"
                               maxlength="20" placeholder="{{ __('e.g. RJ14 AB 1234') }}">
                    </div>

                    <div class="col-sm-5">
                        <label class="form-label" for="ins-expiry">{{ __('Current policy ends') }}</label>
                        <input type="date" id="ins-expiry" name="previous_policy_expiry" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="ins-partner">{{ __('Preferred insurer') }}</label>
                        <select id="ins-partner" name="insurance_partner_id" class="form-select">
                            <option value="">{{ __('Show me all of them') }}</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner['id'] }}">{{ $partner['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="ins-state">{{ __('State') }}</label>
                        <select id="ins-state" name="state_id" class="form-select">
                            <option value="">{{ __('Select a state') }}</option>
                            @foreach ($states as $state)
                                <option value="{{ $state['id'] }}" @selected($selectedState === $state['id'])>{{ $state['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ins-claims" name="has_claim_history" value="1">
                            <label class="form-check-label small" for="ins-claims">
                                {{ __('I have claimed on this tractor in the last year') }}
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="button" class="btn btn-primary w-100" id="ins-submit">{{ __('Get quotes') }}</button>
                        <p class="small text-muted-2 mt-2 mb-0">
                            {{ __('We pass your details only to the insurer you pick. Never to dealers.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(function () {
    var form = $('#insurance-form');

    $('#ins-send-otp').on('click', async function () {
        var mobile = $('#ins-mobile').val();

        if (!/^[6-9]\d{9}$/.test(mobile)) {
            return KJ.toast('{{ __('Enter a valid 10-digit mobile number.') }}', 'danger');
        }

        var button = $(this).prop('disabled', true);
        var response = await KJ.request({
            url: '{{ route('ajax.leads.otp') }}', method: 'POST', data: { mobile: mobile },
        });
        button.prop('disabled', false);

        if (response.status === 'ok') {
            document.getElementById('ins-otp-row').hidden = false;
            $('#ins-otp').trigger('focus');
        }

        KJ.toast(response.message, response.status === 'ok' ? 'success' : 'danger');
    });

    $('#ins-submit').on('click', async function () {
        var button = $(this).prop('disabled', true);

        var payload = {};
        form.find('input, select').each(function () {
            if (this.type === 'checkbox') {
                payload[this.name] = this.checked ? 1 : 0;
            } else if (this.name) {
                payload[this.name] = $(this).val();
            }
        });

        var response = await KJ.request({
            url: '{{ route('ajax.insurance.store') }}', method: 'POST', data: payload,
        });

        if (response.status === 'ok') {
            form.html('<h2 class="h6 mb-2">{{ __('Enquiry received') }}</h2>' +
                '<p class="small mb-0"></p>').find('p').text(response.message);
            return;
        }

        button.prop('disabled', false);
        KJ.showErrors(form, response.errors);
        KJ.toast(response.message, 'danger');
    });
});
</script>
@endpush
