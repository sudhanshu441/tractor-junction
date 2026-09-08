@extends('layouts.admin')

@section('title', __('Inspections — Krishi Junction Admin'))
@section('page_title', __('Inspections'))

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('Requested') => [$counts['requested'], $counts['requested'] > 0],
        __('Scheduled') => [$counts['scheduled'], false],
        __('Awaiting approval') => [$counts['completed'], $counts['completed'] > 0],
    ] as $label => [$value, $alert])
        <div class="col-12 col-md-4">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach ([
        '' => __('All'),
        'requested' => __('Requested'),
        'scheduled' => __('Scheduled'),
        'in_progress' => __('In progress'),
        'completed' => __('Completed'),
        'cancelled' => __('Cancelled'),
    ] as $value => $label)
        <a href="{{ route('admin.inspections.index', array_filter(['status' => $value])) }}"
           class="btn btn-sm {{ request()->query('status', '') === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Reference') }}</th><th>{{ __('Machine') }}</th><th>{{ __('Location') }}</th>
                    <th>{{ __('Inspector') }}</th><th>{{ __('Scheduled') }}</th>
                    <th class="num">{{ __('Score') }}</th><th>{{ __('Status') }}</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($inspections as $inspection)
                @php
                    $badge = match ($inspection->status) {
                        'completed' => $inspection->isApproved() ? 'badge-ok' : 'badge-info',
                        'scheduled', 'in_progress' => 'badge-info',
                        'requested' => 'badge-warn',
                        default => 'badge-muted',
                    };
                @endphp
                <tr>
                    <td class="mono small">{{ $inspection->reference_no }}</td>
                    <td>
                        <b>{{ $inspection->listing?->title ?: __('Listing removed') }}</b>
                        <div class="small text-muted-2 mono">{{ $inspection->listing?->reference_no }}</div>
                    </td>
                    <td class="small">{{ $inspection->listing?->city?->name ?? '—' }}</td>
                    <td class="small">{{ $inspection->inspector?->name ?? __('unassigned') }}</td>
                    <td class="small text-muted-2">{{ $inspection->scheduled_at?->format('d M Y, H:i') ?? '—' }}</td>
                    <td class="num mono">
                        @if ($inspection->overall_score !== null)
                            {{ number_format((float) $inspection->overall_score, 1) }}
                            <span class="badge badge-muted">{{ $inspection->grade }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $inspection->status) }}</span>
                        @if ($inspection->isApproved())
                            <span class="badge badge-ok">{{ __('approved') }}</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @can('inspections.assign')
                            @if (in_array($inspection->status, ['requested', 'scheduled'], true))
                                <button type="button" class="btn btn-sm btn-outline-primary js-schedule"
                                        data-url="{{ route('admin.inspections.schedule', $inspection) }}"
                                        data-reference="{{ $inspection->reference_no }}"
                                        data-inspector="{{ $inspection->inspector_id }}"
                                        data-at="{{ $inspection->scheduled_at?->format('Y-m-d\TH:i') }}">
                                    {{ $inspection->status === 'requested' ? __('Schedule') : __('Reschedule') }}
                                </button>
                            @endif
                        @endcan
                        @if ($inspection->inspector_id)
                            <a class="btn btn-sm btn-outline-secondary"
                               href="{{ route('admin.inspections.form', $inspection) }}">{{ __('Report') }}</a>
                        @endif
                        @can('inspections.approve')
                            @if ($inspection->status === 'completed' && ! $inspection->isApproved())
                                <button type="button" class="btn btn-sm btn-primary js-approve"
                                        data-url="{{ route('admin.inspections.approve', $inspection) }}">
                                    {{ __('Approve') }}
                                </button>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted-2 py-4">{{ __('No inspections yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $inspections->links() }}</div>

<div class="modal fade" id="schedule-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="h6 modal-title">{{ __('Schedule inspection') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted-2 mono mb-3" id="schedule-reference"></p>

                <label class="form-label" for="schedule-inspector">{{ __('Inspector') }}</label>
                <select id="schedule-inspector" class="form-select form-select-sm mb-3">
                    <option value="">{{ __('Choose an inspector') }}</option>
                    @foreach ($inspectors as $inspector)
                        <option value="{{ $inspector->id }}">{{ $inspector->name }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="schedule-at">{{ __('Date and time') }}</label>
                <input type="datetime-local" id="schedule-at" class="form-control form-control-sm"
                       min="{{ now()->format('Y-m-d\TH:i') }}">
                <p class="small text-muted-2 mt-2 mb-0">{{ __('The seller is told the date as soon as you save.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary btn-sm" id="schedule-save">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var modalEl = document.getElementById('schedule-modal');
    var modal = new bootstrap.Modal(modalEl);
    var target = null;

    $('.js-schedule').on('click', function () {
        target = $(this).data('url');
        $('#schedule-reference').text($(this).data('reference'));
        $('#schedule-inspector').val($(this).data('inspector') || '');
        $('#schedule-at').val($(this).data('at') || '');
        modal.show();
    });

    $('#schedule-save').on('click', async function () {
        var button = $(this).prop('disabled', true);
        var response = await KJ.request({
            url: target,
            method: 'POST',
            data: {
                inspector_id: $('#schedule-inspector').val(),
                scheduled_at: $('#schedule-at').val(),
            },
        });
        button.prop('disabled', false);

        if (response.status === 'ok') {
            modal.hide();
            KJ.toast(response.message, 'success');
            setTimeout(function () { window.location.reload(); }, 600);
        } else {
            KJ.toast(response.message || '{{ __('Could not schedule that inspection.') }}', 'danger');
        }
    });

    $('.js-approve').on('click', async function () {
        var button = $(this).prop('disabled', true);
        var response = await KJ.request({ url: button.data('url'), method: 'POST' });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            setTimeout(function () { window.location.reload(); }, 600);
        } else {
            button.prop('disabled', false);
            KJ.toast(response.message || '{{ __('Could not approve that report.') }}', 'danger');
        }
    });
});
</script>
@endpush
