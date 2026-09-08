@extends('layouts.admin')

@section('title', __('Loan applications — Krishi Junction Admin'))
@section('page_title', __('Loan applications'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('All applications') => [$counts['all'], false],
        __('Open') => [$counts['open'], false],
        __('Documents pending') => [$counts['docs_pending'], $counts['docs_pending'] > 0],
        __('Sanctioned') => [$counts['sanctioned'], false],
    ] as $label => [$value, $alert])
        <div class="col-6 col-lg-3">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <select id="filter-status" class="form-select form-select-sm" style="width:auto;">
        <option value="">{{ __('Any status') }}</option>
        @foreach (['submitted', 'under_review', 'docs_pending', 'sent_to_lender', 'sanctioned', 'disbursed', 'rejected'] as $s)
            <option value="{{ $s }}">{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
        @endforeach
    </select>
</div>

<div class="card"><div class="card-body">
    <table id="loan-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th>{{ __('Reference') }}</th><th>{{ __('Applicant') }}</th>
                <th class="num">{{ __('Amount') }}</th><th class="num">{{ __('EMI') }}</th>
                <th>{{ __('Tenure') }}</th><th>{{ __('District') }}</th>
                <th>{{ __('Status') }}</th><th>{{ __('Submitted') }}</th><th></th>
            </tr>
        </thead>
    </table>
</div></div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    var cls = {
        sanctioned: 'badge-ok', disbursed: 'badge-ok', rejected: 'badge-bad',
        docs_pending: 'badge-warn', under_review: 'badge-info', sent_to_lender: 'badge-info',
    };

    var table = $('#loan-table').DataTable({
        processing: true, serverSide: true, ordering: false,
        ajax: {
            url: '{{ route('admin.loans.data') }}', type: 'POST',
            data: function (d) { d.status = $('#filter-status').val(); },
        },
        columns: [
            { data: 'reference', className: 'mono small' },
            { data: null, render: function (row) {
                return '<b>' + row.applicant + '</b><div class="small text-muted-2 mono">' + (row.mobile || '—') + '</div>';
            }},
            { data: 'amount', className: 'num mono' },
            { data: 'emi', className: 'num mono' },
            { data: 'tenure', className: 'small mono' },
            { data: 'district', className: 'small' },
            { data: 'status', render: function (s) {
                return '<span class="badge ' + (cls[s] || 'badge-muted') + '">' + s.replace(/_/g, ' ') + '</span>';
            }},
            { data: 'submitted', className: 'small text-muted-2' },
            { data: null, className: 'text-end', render: function (row) {
                return '<a class="btn btn-sm btn-outline-primary" href="' + row.detail_url + '">{{ __('Open') }}</a>';
            }},
        ],
        pageLength: 25,
        language: { emptyTable: '{{ __('No loan applications yet.') }}' },
    });

    $('#filter-status').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
