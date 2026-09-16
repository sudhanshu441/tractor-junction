{{--
    Shown once per visitor, on high-intent pages only, after they have shown
    real interest — a set dwell time or a move toward the tab bar.

    It asks rather than extracts. A browser cannot be made to give up a name or
    a number, and nothing here tries: the visitor types both, ticks a consent
    box, and can dismiss it for good.
--}}
@php
    $intent = app(\App\Domain\Analytics\Services\VisitorJourney::class)->intentFor(request()->path());
    $show = in_array($intent, \App\Domain\Analytics\Services\VisitorJourney::PROMPT_ON, true);
@endphp

@if ($show && ! auth()->check())
    <div class="kj-callback" id="kj-callback" role="dialog" aria-modal="false"
         aria-labelledby="kj-callback-title" hidden>
        <button type="button" class="kj-callback-close" id="kj-callback-dismiss"
                aria-label="{{ __('Close') }}">&times;</button>

        <div class="kj-callback-body">
            <h2 class="h6 mb-1" id="kj-callback-title">
                @switch($intent)
                    @case('sell') {{ __('Want help selling your tractor?') }} @break
                    @case('finance') {{ __('Want help with the loan?') }} @break
                    @default {{ __('Want help choosing?') }}
                @endswitch
            </h2>
            <p class="small text-muted-2 mb-3">
                {{ __('Leave your number and someone who knows these machines will call you. No charge, and we do not pass your number to anyone you have not asked about.') }}
            </p>

            <form id="kj-callback-form">
                <input type="hidden" name="interest" value="{{ $intent }}">

                <label class="form-label" for="kj-callback-name">{{ __('Your name') }}</label>
                <input type="text" id="kj-callback-name" name="name" class="form-control form-control-sm mb-2"
                       maxlength="100" required autocomplete="name">

                <label class="form-label" for="kj-callback-mobile">{{ __('Mobile number') }}</label>
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text">+91</span>
                    <input type="tel" id="kj-callback-mobile" name="mobile" class="form-control"
                           inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" required
                           autocomplete="tel-national">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="kj-callback-consent" name="consent" value="1">
                    <label class="form-check-label small" for="kj-callback-consent">
                        {{ __('You may call or message me about this.') }}
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Call me back') }}</button>
                <button type="button" class="btn btn-link btn-sm w-100 mt-1" id="kj-callback-never">
                    {{ __('No thanks, do not ask again') }}
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    $(function () {
        var panel = document.getElementById('kj-callback');
        var KEY = 'kj_callback_state';
        var state = null;

        try { state = localStorage.getItem(KEY); } catch (e) { /* private mode */ }

        // "never" is permanent; "done" means they already gave us a number.
        if (state === 'never' || state === 'done') { return; }

        var shown = false;

        function remember(value) {
            try { localStorage.setItem(KEY, value); } catch (e) {}
        }

        function show() {
            if (shown) { return; }
            shown = true;
            panel.hidden = false;
            panel.classList.add('is-open');
        }

        function hide() { panel.hidden = true; panel.classList.remove('is-open'); }

        // Enough time on the page to mean something, or a move toward the tabs.
        var timer = setTimeout(show, 35000);

        $(document).on('mouseleave.kjcb', function (event) {
            if (event.clientY <= 0) { show(); }
        });

        $('#kj-callback-dismiss').on('click', function () {
            clearTimeout(timer);
            hide();
        });

        $('#kj-callback-never').on('click', function () {
            remember('never');
            clearTimeout(timer);
            hide();
        });

        $('#kj-callback-form').on('submit', async function (event) {
            event.preventDefault();
            var form = this;
            var button = $(form).find('[type="submit"]').prop('disabled', true);

            var response = await KJ.request({
                url: '{{ route('ajax.callback.store') }}',
                method: 'POST',
                data: $(form).serialize() + ($('#kj-callback-consent').is(':checked') ? '' : '&consent=0'),
            });

            if (response.status === 'ok') {
                remember('done');
                $('.kj-callback-body').html(
                    '<h2 class="h6 mb-1">{{ __('Thank you') }}</h2><p class="small mb-0"></p>'
                );
                $('.kj-callback-body p').text(response.message);
                setTimeout(hide, 4000);
                return;
            }

            button.prop('disabled', false);
            KJ.showErrors(form, response.errors);
            KJ.toast(response.message, 'danger');
        });
    });
    </script>
    @endpush
@endif
