@extends('layouts.admin')

@section('title', __('Insurance enquiries — Krishi Junction Admin'))
@section('page_title', __('Insurance enquiries'))

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('New') => [$counts['new'], $counts['new'] > 0],
        __('Contacted') => [$counts['contacted'], false],
        __('Quoted') => [$counts['quoted'], false],
        __('Converted') => [$counts['converted'], false],
        __('Lost') => [$counts['lost'], false],
    ] as $label => [$value, $alert])
        <div class="col-6 col-lg">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

@if ($expiringSoon)
    <div class="alert alert-warning small">
        {{ trans_choice(
            '{1} One open enquiry has a policy expiring within 30 days — that is the call to make first.
             |[2,*] :count open enquiries have policies expiring within 30 days — those are the calls to make first.',
            $expiringSoon, ['count' => $expiringSoon]) }}
    </div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.insurance.index') }}"
           class="btn btn-sm {{ $status ? 'btn-outline-secondary' : 'btn-primary' }}">{{ __('All') }}</a>
        @foreach (['new' => __('New'), 'contacted' => __('Contacted'), 'quoted' => __('Quoted'), 'converted' => __('Converted'), 'lost' => __('Lost')] as $value => $label)
            <a href="{{ route('admin.insurance.index', ['status' => $value]) }}"
               class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    @can('insurance.view')
        <div class="d-flex gap-2">
            @foreach (['xlsx' => __('Excel'), 'csv' => __('CSV'), 'pdf' => __('PDF')] as $format => $label)
                <a href="{{ route('admin.reports.download', ['report' => 'insurance', 'format' => $format]) }}"
                   class="btn btn-outline-primary btn-sm">{{ $label }}</a>
            @endforeach
        </div>
    @endcan
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Reference') }}</th><th>{{ __('Applicant') }}</th>
                    <th>{{ __('Cover') }}</th><th>{{ __('Machine') }}</th>
                    <th>{{ __('Policy ends') }}</th><th>{{ __('Insurer') }}</th>
                    <th>{{ __('Owner') }}</th><th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($enquiries as $enquiry)
                @php
                    $badge = match ($enquiry->status) {
                        'converted' => 'badge-ok', 'lost' => 'badge-bad',
                        'new' => 'badge-warn', default => 'badge-info',
                    };
                    $expiring = $enquiry->previous_policy_expiry
                        && $enquiry->previous_policy_expiry->between(today(), today()->addDays(30));
                @endphp
                <tr class="js-row" data-url="{{ route('admin.insurance.update', $enquiry) }}">
                    <td class="mono small">
                        {{ $enquiry->reference_no }}
                        <div class="small text-muted-2">{{ $enquiry->created_at->diffForHumans() }}</div>
                    </td>
                    <td>
                        <b class="small">{{ $enquiry->applicant_name }}</b>
                        <div class="small text-muted-2 mono">
                            {{ auth()->user()->can('leads.view_contact') ? $enquiry->mobile : $enquiry->masked_mobile }}
                        </div>
                    </td>
                    <td class="small">{{ str_replace('_', ' ', $enquiry->coverage_type) }}</td>
                    <td class="small">
                        {{ $enquiry->product ? trim($enquiry->product->brand?->name.' '.$enquiry->product->name) : '—' }}
                        @if ($enquiry->registration_number)
                            <div class="small text-muted-2 mono">{{ $enquiry->registration_number }}</div>
                        @endif
                    </td>
                    <td class="small">
                        @if ($enquiry->previous_policy_expiry)
                            <span class="{{ $expiring ? 'badge badge-warn' : 'text-muted-2' }}">
                                {{ $enquiry->previous_policy_expiry->format('d M Y') }}
                            </span>
                        @else
                            <span class="text-muted-2">—</span>
                        @endif
                    </td>
                    <td>
                        <select class="form-select form-select-sm js-partner" style="min-width:9rem;"
                                aria-label="{{ __('Insurer') }}">
                            <option value="">{{ __('Not chosen') }}</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}" @selected($enquiry->insurance_partner_id === $partner->id)>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm js-assignee" style="min-width:9rem;"
                                aria-label="{{ __('Owner') }}">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($advisors as $advisor)
                                <option value="{{ $advisor->id }}" @selected($enquiry->assigned_to === $advisor->id)>
                                    {{ $advisor->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <span class="badge {{ $badge }} mb-1">{{ $enquiry->status }}</span>
                        @php $next = \App\Http\Controllers\Admin\InsuranceController::TRANSITIONS[$enquiry->status] ?? []; @endphp
                        @if ($next)
                            <select class="form-select form-select-sm js-status" aria-label="{{ __('Move to') }}">
                                <option value="">{{ __('Move to…') }}</option>
                                @foreach ($next as $to)
                                    <option value="{{ $to }}">{{ ucfirst($to) }}</option>
                                @endforeach
                            </select>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted-2 py-4">
                        {{ __('No insurance enquiries yet.') }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $enquiries->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    async function save(row, payload) {
        var response = await KJ.request({ url: row.data('url'), method: 'POST', data: payload });

        KJ.toast(response.message, response.status === 'ok' ? 'success' : 'danger');

        if (response.status === 'ok' && payload.status) {
            setTimeout(function () { window.location.reload(); }, 600);
        }
    }

    $('.js-partner').on('change', function () {
        save($(this).closest('.js-row'), { insurance_partner_id: $(this).val() || null });
    });

    $('.js-assignee').on('change', function () {
        save($(this).closest('.js-row'), { assigned_to: $(this).val() || null });
    });

    $('.js-status').on('change', function () {
        if (!$(this).val()) { return; }
        save($(this).closest('.js-row'), { status: $(this).val() });
    });
});
</script>
@endpush
