@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/ {{ __('Contact us') }}</span>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <h1 class="h4 mb-1">{{ __('Talk to us') }}</h1>
            <p class="text-muted-2" style="max-width: 58ch;">
                {{ __('A question about a listing, a dealer, a loan application — or something that went wrong. We answer in Hindi and English.') }}
            </p>

            <div class="kj-panel p-4 mt-3" id="contact-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="contact-name">{{ __('Your name') }}</label>
                        <input type="text" id="contact-name" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact-mobile">{{ __('Mobile number') }}</label>
                        <input type="tel" id="contact-mobile" name="mobile" class="form-control"
                               inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact-email">{{ __('Email') }}</label>
                        <input type="email" id="contact-email" name="email" class="form-control" maxlength="150">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact-subject">{{ __('Subject') }}</label>
                        <input type="text" id="contact-subject" name="subject" class="form-control" maxlength="150">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact-message">{{ __('Your message') }}</label>
                        <textarea id="contact-message" name="message" class="form-control" rows="4"
                                  minlength="10" maxlength="2000" required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="contact-submit">{{ __('Send message') }}</button>
                        <p class="small text-muted-2 mt-2 mb-0">
                            {{ __('Leave a mobile number or an email, otherwise we cannot reply.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <aside class="col-lg-5">
            <div class="kj-panel p-4">
                <h2 class="h6 mb-3">{{ __('Reach us directly') }}</h2>
                @if (\App\Models\Setting::get('support_mobile'))
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="small text-muted-2">{{ __('Phone') }}</span>
                        <a class="mono" href="tel:{{ \App\Models\Setting::get('support_mobile') }}">{{ \App\Models\Setting::get('support_mobile') }}</a>
                    </div>
                @endif
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-muted-2">{{ __('Email') }}</span>
                    <a class="mono" href="mailto:{{ \App\Models\Setting::get('support_email') }}">{{ \App\Models\Setting::get('support_email') }}</a>
                </div>
                @if (\App\Models\Setting::get('address'))
                    <div class="py-2">
                        <span class="small text-muted-2 d-block">{{ __('Registered address') }}</span>
                        <span class="small">{{ \App\Models\Setting::get('address') }}</span>
                    </div>
                @endif
            </div>

            @if (count($faqs))
                <div class="kj-panel p-4 mt-3">
                    <h2 class="h6 mb-2">{{ __('Answered already?') }}</h2>
                    <ul class="small ps-3 mb-2">
                        @foreach (array_slice($faqs, 0, 4) as $faq)
                            <li class="mb-1">{{ $faq['question'] }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('faqs.index') }}" class="small">{{ __('Read all answers') }}</a>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#contact-submit').on('click', async function () {
        var form = $('#contact-form');
        var button = $(this).prop('disabled', true);

        var payload = {};
        form.find('input, textarea').each(function () { if (this.name) { payload[this.name] = $(this).val(); } });

        var response = await KJ.request({
            url: '{{ route('ajax.contact.store') }}', method: 'POST', data: payload,
        });

        if (response.status === 'ok') {
            form.html('<h2 class="h6 mb-2">{{ __('Message received') }}</h2><p class="small mb-0"></p>');
            return form.find('p').text(response.message);
        }

        button.prop('disabled', false);
        KJ.showErrors(form, response.errors);
        KJ.toast(response.message, 'danger');
    });
});
</script>
@endpush
