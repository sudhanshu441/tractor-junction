@extends('layouts.admin')

@section('title', __('Translations — Krishi Junction Admin'))
@section('page_title', __('Interface language'))

@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <p class="small text-muted-2 mb-2" style="max-width: 62ch;">
            {{ __('Every string on the website, with the shipped translation beside it. Type a correction and it takes effect immediately — it also survives the next deploy. Clear a box to go back to the shipped wording.') }}
        </p>
        <div class="d-flex gap-2">
            @foreach ($locales as $code => $label)
                @continue($code === 'en')
                <a href="{{ route('admin.seo.translations', ['locale' => $code]) }}"
                   class="btn btn-sm {{ $locale === $code ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="locale" value="{{ $locale }}">
        <input type="search" name="q" class="form-control form-control-sm" style="width: 16rem;"
               value="{{ $search }}" placeholder="{{ __('Search a word…') }}">
        <button class="btn btn-outline-primary btn-sm" type="submit">{{ __('Search') }}</button>
    </form>
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:35%;">{{ __('English') }}</th>
                    <th style="width:30%;">{{ __('Shipped translation') }}</th>
                    <th style="width:35%;">{{ __('Your wording') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($keys as $key)
                <tr>
                    <td class="small">{{ $key }}</td>
                    <td class="small text-muted-2">{{ $strings[$key] ?? '—' }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm js-translation"
                               data-key="{{ $key }}" value="{{ $overrides[$key] ?? '' }}"
                               placeholder="{{ $strings[$key] ?? __('not translated yet') }}"
                               aria-label="{{ __('Translation for :key', ['key' => $key]) }}">
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted-2 py-4">{{ __('No strings match that search.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

@if ($total > count($keys))
    <p class="small text-muted-2 mt-2">
        {{ __('Showing :shown of :total strings. Use the search to narrow it down.', ['shown' => count($keys), 'total' => $total]) }}
    </p>
@endif
@endsection

@push('scripts')
<script>
$(function () {
    var timers = {};

    // Saved on blur or after a pause: an editor working through a long list
    // should never have to find a save button.
    $('.js-translation').on('change blur', async function () {
        var input = $(this);
        var key = input.data('key');

        clearTimeout(timers[key]);
        timers[key] = setTimeout(async function () {
            input.addClass('is-saving');

            var response = await KJ.request({
                url: '{{ route('admin.seo.translations.save') }}',
                method: 'POST',
                data: { locale: '{{ $locale }}', key: key, value: input.val() },
            });

            input.removeClass('is-saving');

            if (response.status === 'ok') {
                input.css('border-color', 'var(--kj-green-700)');
                setTimeout(function () { input.css('border-color', ''); }, 900);
            } else {
                KJ.toast(response.message, 'danger');
            }
        }, 200);
    });
});
</script>
@endpush
