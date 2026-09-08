@extends('layouts.admin')

@section('title', __('Loan :ref — Krishi Junction Admin', ['ref' => $application->reference_no]))
@section('page_title', __('Loan :ref', ['ref' => $application->reference_no]))

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h6 mb-1">{{ $application->applicant_name }}</h2>
                    <p class="small text-muted-2 mb-0 mono">
                        {{ $canSeeContact ? $application->mobile : substr($application->mobile, 0, 2).'XXXXXX'.substr($application->mobile, -2) }}
                        @if ($application->email) · {{ $application->email }} @endif
                    </p>
                </div>
                <span class="badge badge-muted">{{ str_replace('_', ' ', $application->status) }}</span>
            </div>

            <div class="row g-3">
                @foreach ([
                    __('Machinery price') => '₹'.number_format((float) $application->machinery_price),
                    __('Down payment') => '₹'.number_format((float) $application->down_payment),
                    __('Loan amount') => '₹'.number_format((float) $application->loan_amount),
                    __('Indicative EMI') => '₹'.number_format((float) $application->calculated_emi),
                    __('Tenure') => $application->tenure_months.' '.__('months'),
                    __('Rate') => $application->expected_interest_rate.'%',
                    __('Annual income') => '₹'.number_format((float) $application->annual_income),
                    __('Land holding') => $application->land_holding_acres.' '.__('acres'),
                ] as $label => $value)
                    <div class="col-6 col-md-3">
                        <div class="kj-stat"><div class="k">{{ $label }}</div>
                        <div class="v" style="font-size:.95rem;">{{ $value }}</div></div>
                    </div>
                @endforeach
            </div>

            <div class="alert alert-secondary small mt-3 mb-0">
                <b>{{ __('KYC on file:') }}</b>
                {{ __('PAN') }} <span class="mono">{{ $application->pan_masked ?: '—' }}</span> ·
                {{ __('Aadhaar') }} <span class="mono">{{ $application->aadhaar_masked ?: '—' }}</span>.
                {{ __('Only the last four digits are stored.') }}
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Documents') }}</h2>

            @if ($missing)
                <div class="alert alert-warning small">
                    <b>{{ __('Missing:') }}</b>
                    {{ implode(', ', array_map(fn ($d) => str_replace('_', ' ', $d), $missing)) }}
                </div>
            @endif

            @forelse ($application->documents as $document)
                <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                    <div>
                        <b class="small">{{ ucfirst(str_replace('_', ' ', $document->doc_type)) }}</b>
                        <div class="small text-muted-2">{{ $document->original_name }}</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge {{ $document->status === 'verified' ? 'badge-ok' : ($document->status === 'rejected' ? 'badge-bad' : 'badge-warn') }}">
                            {{ $document->status }}
                        </span>
                        <a href="{{ $documentLinks[$document->id] ?? '#' }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                        @can('loans.edit')
                            <button class="btn btn-sm btn-outline-secondary js-verify-doc" data-status="verified"
                                    data-url="{{ route('admin.loans.documents.verify', $document) }}">✓</button>
                            <button class="btn btn-sm btn-outline-secondary js-verify-doc" data-status="rejected"
                                    data-url="{{ route('admin.loans.documents.verify', $document) }}">✕</button>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('No documents uploaded.') }}</p>
            @endforelse
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('History') }}</h2>
            @forelse ($application->statusLogs as $log)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div class="small">
                        <span class="mono text-muted-2">{{ $log->from_status }} &rarr; {{ $log->to_status }}</span>
                        @if ($log->remarks)<div>{{ $log->remarks }}</div>@endif
                    </div>
                    <span class="small text-muted-2 text-nowrap">
                        {{ $log->changedBy?->name }}<br>{{ $log->created_at->diffForHumans() }}
                    </span>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('Nothing logged yet.') }}</p>
            @endforelse
        </div></div>
    </div>

    <div class="col-lg-5">
        @can('loans.edit')
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Move it along') }}</h2>

                @if (count($transitions))
                    <select id="new-status" class="form-select form-select-sm mb-2">
                        @foreach ($transitions as $t)
                            <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                    <textarea id="status-remarks" class="form-control form-control-sm mb-2" rows="2"
                              placeholder="{{ __('Remarks (the applicant may see these)') }}"></textarea>
                    <button class="btn btn-primary btn-sm w-100" id="save-status">{{ __('Update status') }}</button>
                @else
                    <p class="small text-muted-2 mb-0">{{ __('This application is closed.') }}</p>
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-1">{{ __('Send to lenders') }}</h2>
                <p class="small text-muted-2">
                    {{ __('Only lenders whose published criteria this application actually meets.') }}
                </p>

                @forelse ($matchingLenders as $lender)
                    <div class="form-check">
                        <input class="form-check-input js-lender" type="checkbox" value="{{ $lender->id }}"
                               id="lender-{{ $lender->id }}">
                        <label class="form-check-label small d-flex justify-content-between" for="lender-{{ $lender->id }}">
                            <span>{{ $lender->name }}</span>
                            <span class="text-muted-2 mono">{{ $lender->interest_min }}–{{ $lender->interest_max }}%</span>
                        </label>
                    </div>
                @empty
                    <p class="small text-muted-2">{{ __('No lender matches this amount, tenure and state.') }}</p>
                @endforelse

                @if ($matchingLenders->isNotEmpty())
                    <button class="btn btn-outline-primary btn-sm w-100 mt-2" id="send-lenders">{{ __('Send application') }}</button>
                @endif
            </div></div>

            @if ($application->lenders->isNotEmpty())
                <div class="card"><div class="card-body">
                    <h2 class="h6 mb-2">{{ __('With lenders') }}</h2>
                    @foreach ($application->lenders as $lender)
                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                            <div>
                                <b class="small">{{ $lender->name }}</b>
                                <div class="small text-muted-2">
                                    {{ $lender->pivot->sent_at ? \Carbon\Carbon::parse($lender->pivot->sent_at)->diffForHumans() : '' }}
                                </div>
                            </div>
                            <select class="form-select form-select-sm js-decision" style="width:auto;"
                                    data-lender="{{ $lender->id }}">
                                @foreach (['sent', 'acknowledged', 'sanctioned', 'rejected'] as $d)
                                    <option value="{{ $d }}" @selected($lender->pivot->status === $d)>{{ ucfirst($d) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div></div>
            @endif
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#save-status').on('click', function () {
        KJ.request({
            url: '{{ route('admin.loans.status', $application) }}', method: 'POST',
            data: { status: $('#new-status').val(), remarks: $('#status-remarks').val() },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });

    $('#send-lenders').on('click', function () {
        var ids = $('.js-lender:checked').map(function () { return this.value; }).get();
        if (!ids.length) { return KJ.toast('Pick at least one lender.', 'warning'); }

        KJ.request({
            url: '{{ route('admin.loans.lenders', $application) }}', method: 'POST',
            data: { lender_ids: ids },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });

    $('.js-decision').on('change', function () {
        KJ.request({
            url: '{{ route('admin.loans.lender-decision', $application) }}', method: 'POST',
            data: { lender_id: $(this).data('lender'), decision: $(this).val() },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });

    $('.js-verify-doc').on('click', function () {
        KJ.request({
            url: $(this).data('url'), method: 'POST', data: { status: $(this).data('status') },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });
});
</script>
@endpush
