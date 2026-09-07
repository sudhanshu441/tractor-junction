@extends('layouts.admin')

@section('title', __('Moderation queue — Krishi Junction Admin'))
@section('page_title', __('Moderation queue'))

@section('content')
@if (! $listing)
    <div class="kj-panel p-5 text-center">
        <span class="badge badge-ok mb-2">{{ __('Queue clear') }}</span>
        <p class="fw-semibold mb-1">{{ __('Nothing is waiting for review') }}</p>
        <p class="small text-muted-2 mb-0">{{ __('New listings appear here as sellers submit them.') }}</p>
    </div>
@else
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <span class="badge badge-muted mono">{{ __(':n of :total', ['n' => $position, 'total' => $total]) }}</span>
            @if ($oldestWait)
                @php $waited = $oldestWait->diffInHours(now()); @endphp
                <span class="badge {{ $waited >= $slaHours ? 'badge-bad' : 'badge-warn' }}">
                    {{ __('oldest waiting :time · SLA :sla h', ['time' => $oldestWait->diffForHumans(null, true), 'sla' => $slaHours]) }}
                </span>
            @endif
        </div>
        <div class="small text-muted-2">
            <kbd>A</kbd> {{ __('approve') }} · <kbd>C</kbd> {{ __('changes') }} ·
            <kbd>R</kbd> {{ __('reject') }} · <kbd>&rarr;</kbd> {{ __('skip') }}
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h6 mb-1">{{ $listing->title ?: $listing->reference_no }}</h2>
                        <p class="small text-muted-2 mb-0 mono">
                            {{ $listing->reference_no }} ·
                            {{ __('submitted :when', ['when' => $listing->created_at->diffForHumans()]) }} ·
                            {{ $listing->seller?->name }}
                            ({{ auth()->user()->can('leads.view_contact') ? $listing->seller?->mobile : $listing->seller?->masked_mobile }})
                        </p>
                    </div>
                    <span class="badge badge-warn">{{ __('Pending') }}</span>
                </div>

                <div class="row g-2">
                    @forelse ($listing->images as $image)
                        <div class="col-6 col-md-4">
                            <div class="kj-moderation-photo">
                                <a href="{{ $image->url() }}" target="_blank" rel="noopener">
                                    <img src="{{ $image->thumbnailUrl() }}" alt="{{ $image->angle }}" loading="lazy">
                                </a>
                                <div class="angle">{{ str_replace('_', ' ', $image->angle) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning small mb-0">{{ __('This listing has no photos — that alone is grounds to request changes.') }}</div>
                        </div>
                    @endforelse
                </div>

                <div class="row g-3 mt-3">
                    @foreach ([
                        __('Brand') => $listing->brand?->name,
                        __('Model') => $listing->product?->name ?? __('not matched'),
                        __('Year') => $listing->manufacturing_year,
                        __('Engine hours') => $listing->engine_hours ? number_format($listing->engine_hours) : '—',
                        __('Condition') => ucfirst(str_replace('_', ' ', (string) $listing->condition)),
                        __('Asking price') => '₹'.number_format((float) $listing->expected_price),
                        __('Location') => trim(($listing->city?->name ?? '').', '.($listing->state?->name ?? ''), ', '),
                        __('RC / insurance') => ($listing->has_rc ? __('RC yes') : __('RC no')).' / '.($listing->has_insurance ? __('valid') : __('not valid')),
                    ] as $label => $value)
                        <div class="col-6 col-md-3">
                            <div class="kj-stat">
                                <div class="k">{{ $label }}</div>
                                <div class="v" style="font-size:.95rem;">{{ $value }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($listing->description)
                    <div class="mt-3">
                        <div class="kj-label mb-1">{{ __("Seller's description") }}</div>
                        <p class="small mb-0">{{ $listing->description }}</p>
                    </div>
                @endif
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Checks') }}</h2>

                @php
                    $hasPhone = preg_match('/\b[6-9]\d{9}\b/', (string) $listing->description);
                    $hasUrl = preg_match('~https?://|www\.~i', (string) $listing->description);
                    $checks = [
                        [__('Has at least :n photos', ['n' => config('kj.listings.min_photos')]), $listing->images->count() >= config('kj.listings.min_photos')],
                        [__('Matched to a catalogue model'), (bool) $listing->product_id],
                        [__('No phone number in the text'), ! $hasPhone],
                        [__('No links in the text'), ! $hasUrl],
                        [__('Photos not used on another listing'), $duplicateHashes->isEmpty()],
                    ];
                @endphp

                @foreach ($checks as [$label, $passed])
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small">{{ $label }}</span>
                        <span class="badge {{ $passed ? 'badge-ok' : 'badge-warn' }}">
                            {{ $passed ? __('Pass') : __('Check') }}
                        </span>
                    </div>
                @endforeach

                @if ($duplicateHashes->isNotEmpty())
                    <div class="alert alert-warning small mt-3 mb-0">
                        {{ __('The same photos appear on: :refs', ['refs' => $duplicateHashes->implode(', ')]) }}
                    </div>
                @endif

                @if ($assessment && $assessment['status'] !== 'fair')
                    <div class="alert {{ $assessment['status'] === 'above' ? 'alert-warning' : 'alert-secondary' }} small mt-3 mb-0">
                        {{ __('Asking price is :pct% :direction the estimated band of ₹:min – ₹:max.', [
                            'pct' => abs($assessment['deviation_percent']),
                            'direction' => $assessment['status'] === 'above' ? __('above') : __('below'),
                            'min' => number_format($valuation['min']),
                            'max' => number_format($valuation['max']),
                        ]) }}
                    </div>
                @elseif ($valuation)
                    <div class="alert alert-secondary small mt-3 mb-0">
                        {{ __('Price sits inside the estimated band of ₹:min – ₹:max.', [
                            'min' => number_format($valuation['min']),
                            'max' => number_format($valuation['max']),
                        ]) }}
                    </div>
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Decision') }}</h2>

                <label class="form-label" for="reason">{{ __('Reason — the seller sees this verbatim') }}</label>
                <textarea id="reason" class="form-control form-control-sm mb-2" rows="2"
                          placeholder="{{ __('e.g. Photos do not show the machine clearly') }}"></textarea>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary js-decide" data-decision="approve">
                        {{ __('Approve & publish') }} <kbd>A</kbd>
                    </button>
                    <button type="button" class="btn btn-outline-primary js-decide" data-decision="request_changes">
                        {{ __('Request changes') }} <kbd>C</kbd>
                    </button>
                    <button type="button" class="btn btn-outline-secondary js-decide" data-decision="reject"
                            style="color: var(--kj-danger); border-color: var(--kj-danger);">
                        {{ __('Reject') }} <kbd>R</kbd>
                    </button>
                    <button type="button" class="btn btn-outline-secondary js-decide" data-decision="block">
                        {{ __('Block listing') }}
                    </button>
                    <a href="{{ route('admin.listings.queue', ['position' => $position + 1]) }}"
                       class="btn btn-link btn-sm">{{ __('Skip to next') }} <kbd>&rarr;</kbd></a>
                </div>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Seller history') }}</h2>
                @foreach ([
                    __('Listings posted') => $sellerHistory['total'],
                    __('Approved') => $sellerHistory['approved'],
                    __('Rejected or blocked') => $sellerHistory['rejected'],
                ] as $label => $value)
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-muted-2">{{ $label }}</span><span class="mono">{{ $value }}</span>
                    </div>
                @endforeach
                @if ($sellerHistory['member_since'])
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-muted-2">{{ __('Member since') }}</span>
                        <span class="mono">{{ $sellerHistory['member_since']->format('M Y') }}</span>
                    </div>
                @endif
            </div></div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if ($listing)
<script>
$(function () {
    var decideUrl = '{{ route('admin.listings.decide', $listing) }}';
    var nextUrl = '{{ route('admin.listings.queue', ['position' => $position]) }}';
    var busy = false;

    function decide(decision) {
        if (busy) { return; }

        var reason = $('#reason').val();

        if (decision !== 'approve' && !reason) {
            $('#reason').addClass('is-invalid').trigger('focus');
            return KJ.toast('A reason is required — the seller sees it.', 'warning');
        }

        busy = true;
        $('.js-decide').prop('disabled', true);

        KJ.request({ url: decideUrl, method: 'POST', data: { decision: decision, reason: reason } })
            .then(function (res) {
                if (res.status !== 'ok') {
                    busy = false;
                    $('.js-decide').prop('disabled', false);
                    return KJ.toast(res.message, 'danger');
                }
                KJ.toast(res.message, 'success');
                // Stay on the same position: the queue has shifted up by one.
                window.location = nextUrl;
            });
    }

    $('.js-decide').on('click', function () { decide($(this).data('decision')); });

    $(document).on('keydown', function (e) {
        if ($(e.target).is('input, textarea, select')) { return; }

        if (e.key === 'a' || e.key === 'A') { decide('approve'); }
        if (e.key === 'c' || e.key === 'C') { decide('request_changes'); }
        if (e.key === 'r' || e.key === 'R') { decide('reject'); }
        if (e.key === 'ArrowRight') { window.location = '{{ route('admin.listings.queue', ['position' => $position + 1]) }}'; }
    });
});
</script>
@endif
@endpush
