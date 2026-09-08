@extends('layouts.dealer')

@section('title', __('Lead :ref — Krishi Junction', ['ref' => $lead->reference_no]))

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="kj-panel p-4 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h1 class="h5 mb-1">{{ $lead->name }}</h1>
                    <p class="small text-muted-2 mb-0 mono">
                        {{ $lead->mobile }}
                        @if ($lead->mobile_verified)
                            <span class="badge badge-ok ms-1">{{ __('Verified') }}</span>
                        @endif
                    </p>
                </div>
                <a href="tel:+91{{ $lead->mobile }}" class="btn btn-primary btn-sm">{{ __('Call now') }}</a>
            </div>

            <div class="row g-3">
                @foreach ([
                    __('Reference') => $lead->reference_no,
                    __('Received') => $lead->created_at->diffForHumans(),
                    __('Status') => ucfirst($lead->status),
                    __('Location') => $lead->city?->name ?? $lead->district?->name ?? '—',
                ] as $label => $value)
                    <div class="col-6 col-md-3">
                        <div class="kj-stat"><div class="k">{{ $label }}</div>
                        <div class="v" style="font-size:.95rem;">{{ $value }}</div></div>
                    </div>
                @endforeach
            </div>

            @if ($lead->leadable)
                <div class="alert alert-secondary small mt-3 mb-0">
                    <b>{{ __('Interested in:') }}</b>
                    @if ($lead->leadable instanceof \App\Models\Product)
                        {{ $lead->leadable->full_name }}
                    @elseif ($lead->leadable instanceof \App\Models\UsedListing)
                        {{ $lead->leadable->title }}
                    @else
                        {{ __('your dealership') }}
                    @endif
                </div>
            @endif

            @if ($lead->message)
                <div class="mt-3">
                    <div class="kj-label mb-1">{{ __('What they said') }}</div>
                    <p class="small mb-0">{{ $lead->message }}</p>
                </div>
            @endif
        </div>

        <div class="kj-panel p-4">
            <h2 class="h6 mb-3">{{ __('History') }}</h2>
            @forelse ($lead->activities as $activity)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div>
                        <span class="badge badge-muted">{{ str_replace('_', ' ', $activity->activity) }}</span>
                        <div class="small">{{ $activity->description }}</div>
                    </div>
                    <span class="small text-muted-2 text-nowrap">{{ $activity->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('Nothing logged yet.') }}</p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-5">
        <div class="kj-panel p-4 mb-3">
            <h2 class="h6 mb-2">{{ __('Update status') }}</h2>

            @if (count($statuses))
                <select id="new-status" class="form-select form-select-sm mb-2">
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <input type="text" id="status-reason" class="form-control form-control-sm mb-2"
                       placeholder="{{ __('Reason (needed if lost)') }}">
                <button class="btn btn-primary btn-sm w-100" id="save-status">{{ __('Save') }}</button>
            @else
                <p class="small text-muted-2 mb-0">{{ __('This lead is closed.') }}</p>
            @endif
        </div>

        <div class="kj-panel p-4">
            <h2 class="h6 mb-2">{{ __('Log what happened') }}</h2>
            <select id="activity-type" class="form-select form-select-sm mb-2">
                @foreach (['call', 'note', 'whatsapp', 'visit'] as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <textarea id="activity-note" class="form-control form-control-sm mb-2" rows="2"
                      placeholder="{{ __('e.g. Spoke to buyer, visiting Friday') }}"></textarea>
            <label class="form-label" for="follow-up">{{ __('Next follow-up') }}</label>
            <input type="date" id="follow-up" class="form-control form-control-sm mb-2"
                   value="{{ $lead->next_follow_up_at?->toDateString() }}">
            <button class="btn btn-outline-primary btn-sm w-100" id="save-note">{{ __('Add to history') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#save-status').on('click', function () {
        KJ.request({
            url: '{{ route('dealer.leads.status', $lead) }}', method: 'POST',
            data: { status: $('#new-status').val(), reason: $('#status-reason').val() },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });

    $('#save-note').on('click', function () {
        var note = $('#activity-note').val();
        if (!note) { return KJ.toast('Write what happened first.', 'warning'); }

        KJ.request({
            url: '{{ route('dealer.leads.note', $lead) }}', method: 'POST',
            data: {
                note: note,
                activity: $('#activity-type').val(),
                next_follow_up_at: $('#follow-up').val() || null,
            },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });
});
</script>
@endpush
