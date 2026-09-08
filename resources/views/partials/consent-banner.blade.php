@php
    $analyticsConfigured = \App\Models\Setting::get('seo.google_analytics_id') || \App\Models\Setting::get('seo.gtm_id');
@endphp

@if ($analyticsConfigured)
    <div id="kj-consent" class="kj-consent" role="dialog" aria-live="polite"
         aria-label="{{ __('Cookie choice') }}" hidden>
        <div class="container-xl d-flex align-items-center justify-content-between flex-wrap gap-3 py-3">
            <p class="small mb-0" style="max-width: 62ch;">
                {{ __('We use cookies to understand which pages help people and which do not. Nothing is shared with advertisers. You can say no and the website works exactly the same.') }}
                <a href="{{ route('pages.show', 'privacy-policy') }}">{{ __('Privacy policy') }}</a>
            </p>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="kj-consent-deny">{{ __('No thanks') }}</button>
                <button type="button" class="btn btn-primary btn-sm" id="kj-consent-allow">{{ __('Allow') }}</button>
            </div>
        </div>
    </div>

    <script>
    $(function () {
        if (!window.KJConsent || window.KJConsent.decided) { return; }

        var banner = document.getElementById('kj-consent');
        banner.hidden = false;

        $('#kj-consent-allow').on('click', function () { window.KJConsent.grant(); banner.hidden = true; });
        $('#kj-consent-deny').on('click', function () { window.KJConsent.deny(); banner.hidden = true; });
    });
    </script>
@endif
