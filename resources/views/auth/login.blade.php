@extends('layouts.app')

@section('title', __('Log in — Krishi Junction'))

@section('content')
<div class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="kj-panel p-4">
                <h1 class="h5 mb-1">{{ __('Log in or sign up') }}</h1>
                <p class="text-muted-2 small mb-4">{{ __('We will send a one-time code to your mobile number.') }}</p>

                {{-- Step 1: mobile --}}
                <form id="kj-otp-send" novalidate>
                    <label class="form-label" for="mobile">{{ __('Mobile number') }}</label>
                    <div class="input-group">
                        <span class="input-group-text mono">+91</span>
                        <input type="tel" class="form-control mono" id="mobile" name="mobile"
                               inputmode="numeric" maxlength="10" autocomplete="tel-national"
                               placeholder="98XXXXXXXX" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">{{ __('Send code') }}</button>
                </form>

                {{-- Step 2: code --}}
                <form id="kj-otp-verify" class="d-none" novalidate>
                    <p class="small mb-3">
                        {{ __('Code sent to') }} <b class="mono" id="kj-otp-target"></b>
                        <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="kj-otp-change">{{ __('change') }}</button>
                    </p>

                    <label class="form-label" for="otp">{{ __('Enter the 6-digit code') }}</label>
                    <input type="text" class="form-control kj-otp-input" id="otp" name="otp"
                           inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>

                    <label class="form-label mt-3" for="name">{{ __('Your name') }}</label>
                    <input type="text" class="form-control" id="name" name="name" maxlength="100"
                           placeholder="{{ __('Only needed the first time') }}">

                    <button type="submit" class="btn btn-primary w-100 mt-3">{{ __('Verify and continue') }}</button>
                    <button type="button" class="btn btn-link w-100 mt-2" id="kj-otp-resend" disabled>
                        {{ __('Resend code') }} <span id="kj-otp-timer" class="mono"></span>
                    </button>
                </form>

                <hr class="my-4">
                <p class="small text-muted-2 mb-0">
                    {{ __('Staff and dealers can also') }}
                    <a href="{{ route('login.password') }}">{{ __('log in with email and password') }}</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var $send = $('#kj-otp-send'), $verify = $('#kj-otp-verify'), timer = null;

    function startTimer(seconds) {
        clearInterval(timer);
        var left = seconds;
        $('#kj-otp-resend').prop('disabled', true);

        timer = setInterval(function () {
            left--;
            $('#kj-otp-timer').text(left > 0 ? '(' + left + 's)' : '');
            if (left <= 0) {
                clearInterval(timer);
                $('#kj-otp-resend').prop('disabled', false);
            }
        }, 1000);
    }

    function sendOtp() {
        var mobile = $('#mobile').val();

        return KJ.request({
            url: '{{ route('ajax.otp.send') }}',
            method: 'POST',
            data: { mobile: mobile },
        }).then(function (res) {
            if (res.status !== 'ok') {
                KJ.showErrors($send, res.errors);
                if (res.message) { KJ.toast(res.message, 'danger'); }
                return;
            }
            $('#kj-otp-target').text('+91 ' + mobile);
            $send.addClass('d-none');
            $verify.removeClass('d-none');
            $('#otp').trigger('focus');
            startTimer(30);
            KJ.toast(res.message, 'success');
        });
    }

    $send.on('submit', function (e) { e.preventDefault(); sendOtp(); });
    $('#kj-otp-resend').on('click', function () { sendOtp(); });

    $('#kj-otp-change').on('click', function () {
        $verify.addClass('d-none');
        $send.removeClass('d-none');
        $('#mobile').trigger('focus');
    });

    $verify.on('submit', function (e) {
        e.preventDefault();

        KJ.request({
            url: '{{ route('ajax.otp.verify') }}',
            method: 'POST',
            data: { mobile: $('#mobile').val(), otp: $('#otp').val(), name: $('#name').val() },
        }).then(function (res) {
            if (res.status !== 'ok') {
                KJ.showErrors($verify, res.errors);
                KJ.toast(res.message, 'danger');
                return;
            }
            window.location = res.data.redirect;
        });
    });
});
</script>
@endpush
