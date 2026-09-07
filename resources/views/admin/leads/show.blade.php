@extends('layouts.admin')

@section('title', __('Lead :ref — Krishi Junction Admin', ['ref' => $lead->reference_no]))
@section('page_title', __('Lead :ref', ['ref' => $lead->reference_no]))

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h6 mb-1">{{ $lead->name }}</h2>
                    <p class="small text-muted-2 mb-0 mono">
                        {{ $canSeeContact ? $lead->mobile : $lead->masked_mobile }}
                        @if ($lead->email) · {{ $lead->email }} @endif
                    </p>
                </div>
                <span class="badge badge-muted">{{ str_replace('_', ' ', $lead->type) }}</span>
            </div>

            <div class="row g-3">
                @foreach ([
                    __('Status') => ucfirst($lead->status),
                    __('Created') => $lead->created_at->diffForHumans(),
                    __('Source') => $lead->source?->name ?? '—',
                    __('Channel') => $lead->channel,
                    __('District') => $lead->district?->name ?? '—',
                    __('Mobile verified') => $lead->mobile_verified ? __('Yes') : __('No'),
                ] as $label => $value)
                    <div class="col-6 col-md-4">
                        <div class="kj-stat"><div class="k">{{ $label }}</div>
                        <div class="v" style="font-size:.95rem;">{{ $value }}</div></div>
                    </div>
                @endforeach
            </div>

            @if ($lead->leadable)
                <div class="alert alert-secondary small mt-3 mb-0">
                    <b>{{ __('Enquiring about:') }}</b>
                    @if ($lead->leadable instanceof \App\Models\Product)
                        {{ $lead->leadable->full_name }}
                    @elseif ($lead->leadable instanceof \App\Models\UsedListing)
                        {{ $lead->leadable->reference_no }} — {{ $lead->leadable->title }}
                    @elseif ($lead->leadable instanceof \App\Models\Dealer)
                        {{ $lead->leadable->display_name }}
                    @endif
                </div>
            @endif

            @if ($lead->message)
                <div class="mt-3">
                    <div class="kj-label mb-1">{{ __('Message') }}</div>
                    <p class="small mb-0">{{ $lead->message }}</p>
                </div>
            @endif

            @if ($lead->duplicateOf)
                <div class="alert alert-warning small mt-3 mb-0">
                    {{ __('Marked as a duplicate of :ref.', ['ref' => $lead->duplicateOf->reference_no]) }}
                    <a href="{{ route('admin.leads.show', $lead->duplicateOf) }}">{{ __('Open the original') }}</a>
                </div>
            @endif
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('Activity') }}</h2>

            @forelse ($lead->activities as $activity)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div>
                        <span class="badge badge-muted">{{ str_replace('_', ' ', $activity->activity) }}</span>
                        @if ($activity->from_status || $activity->to_status)
                            <span class="small mono text-muted-2">
                                {{ $activity->from_status }} &rarr; {{ $activity->to_status }}
                            </span>
                        @endif
                        <div class="small">{{ $activity->description }}</div>
                    </div>
                    <div class="small text-muted-2 text-nowrap">
                        {{ $activity->user?->name }}<br>{{ $activity->created_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('Nothing logged yet.') }}</p>
            @endforelse
        </div></div>
    </div>

    <div class="col-lg-5">
        @can('leads.edit')
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Update status') }}</h2>

                @if (count($statuses))
                    <select id="new-status" class="form-select form-select-sm mb-2">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="status-reason" class="form-control form-control-sm mb-2"
                           placeholder="{{ __('Reason (required when lost)') }}">
                    <button class="btn btn-primary btn-sm w-100" id="save-status">{{ __('Save status') }}</button>
                @else
                    <p class="small text-muted-2 mb-0">{{ __('This lead is closed — no further transitions.') }}</p>
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Log an activity') }}</h2>
                <select id="activity-type" class="form-select form-select-sm mb-2">
                    @foreach (['call', 'note', 'sms', 'whatsapp', 'email', 'visit'] as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                <textarea id="activity-note" class="form-control form-control-sm mb-2" rows="2"
                          placeholder="{{ __('What happened?') }}"></textarea>
                <label class="form-label" for="follow-up">{{ __('Next follow-up') }}</label>
                <input type="date" id="follow-up" class="form-control form-control-sm mb-2"
                       value="{{ $lead->next_follow_up_at?->toDateString() }}">
                <button class="btn btn-outline-primary btn-sm w-100" id="save-note">{{ __('Add to timeline') }}</button>
            </div></div>
        @endcan

        @can('leads.assign')
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Assign') }}</h2>

                <label class="form-label" for="assign-dealer">{{ __('To a dealer') }}</label>
                <select id="assign-dealer" class="form-select form-select-sm mb-2">
                    <option value="">{{ __('Select dealer') }}</option>
                    @foreach ($dealers as $dealer)
                        <option value="{{ $dealer->id }}">{{ $dealer->display_name }} — {{ $dealer->city?->name }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="assign-staff">{{ __('Or to staff') }}</label>
                <select id="assign-staff" class="form-select form-select-sm mb-2">
                    <option value="">{{ __('Select staff member') }}</option>
                    @foreach ($staff as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>

                <button class="btn btn-primary btn-sm w-100" id="save-assign">{{ __('Assign lead') }}</button>
            </div></div>
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#save-status').on('click', function () {
        KJ.request({
            url: '{{ route('admin.leads.status', $lead) }}', method: 'POST',
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
            url: '{{ route('admin.leads.note', $lead) }}', method: 'POST',
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

    $('#save-assign').on('click', function () {
        var dealerId = $('#assign-dealer').val();
        var staffId = $('#assign-staff').val();

        if (!dealerId && !staffId) { return KJ.toast('Pick a dealer or a staff member.', 'warning'); }

        KJ.request({
            url: '{{ route('admin.leads.assign', $lead) }}', method: 'POST',
            data: dealerId
                ? { assignee_type: 'dealer', dealer_id: dealerId }
                : { assignee_type: 'staff', user_id: staffId },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });
});
</script>
@endpush
