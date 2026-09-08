{{--
    Analytics and consent.

    Nothing that identifies a visitor loads until they choose. Google Consent
    Mode v2 is set to denied *before* the tag loads, so the first pageview of an
    undecided visitor is already cookieless rather than retroactively excused.
--}}
@php
    $ga = \App\Models\Setting::get('seo.google_analytics_id');
    $gtm = \App\Models\Setting::get('seo.gtm_id');
@endphp

@if ($ga || $gtm)
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}

    // Denied until the visitor says otherwise.
    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
        wait_for_update: 500,
    });

    (function () {
        var stored = null;
        try { stored = localStorage.getItem('kj_consent'); } catch (e) { /* private mode */ }

        if (stored === 'granted') {
            gtag('consent', 'update', { analytics_storage: 'granted' });
        }

        window.KJConsent = {
            grant: function () {
                try { localStorage.setItem('kj_consent', 'granted'); } catch (e) {}
                gtag('consent', 'update', { analytics_storage: 'granted' });
            },
            deny: function () {
                try { localStorage.setItem('kj_consent', 'denied'); } catch (e) {}
            },
            decided: stored !== null,
        };
    })();
    </script>

    @if ($gtm)
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm }}');</script>
    @elseif ($ga)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
        <script>
            gtag('js', new Date());
            gtag('config', '{{ $ga }}', { anonymize_ip: true });
        </script>
    @endif
@endif
