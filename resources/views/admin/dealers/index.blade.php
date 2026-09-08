@extends('layouts.admin')

@section('title', __('Dealers — Krishi Junction Admin'))
@section('page_title', __('Dealers'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('All dealers') => [$counts['all'], false],
        __('Awaiting verification') => [$counts['pending'], $counts['pending'] > 0],
        __('Verified') => [$counts['verified'], false],
        __('Suspended') => [$counts['suspended'], false],
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
        @foreach (['pending', 'verified', 'rejected', 'suspended'] as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</div>

<div class="card"><div class="card-body">
    <table id="dealer-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th>{{ __('Code') }}</th><th>{{ __('Dealer') }}</th><th>{{ __('Brands') }}</th>
                <th>{{ __('Location') }}</th><th>{{ __('Contact') }}</th>
                <th class="num">{{ __('Leads') }}</th><th class="num">{{ __('Score') }}</th>
                <th>{{ __('Status') }}</th><th></th>
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
    var cls = { verified: 'badge-ok', pending: 'badge-warn', rejected: 'badge-bad', suspended: 'badge-bad' };

    var table = $('#dealer-table').DataTable({
        processing: true, serverSide: true, ordering: false,
        ajax: {
            url: '{{ route('admin.dealers.data') }}', type: 'POST',
            data: function (d) { d.status = $('#filter-status').val(); },
        },
        columns: [
            { data: 'code', className: 'mono small' },
            { data: null, render: function (row) {
                return '<b>' + row.name + '</b><div class="small text-muted-2">' + row.type + '</div>';
            }},
            { data: 'brands', className: 'small' },
            { data: 'city', className: 'small' },
            { data: 'mobile', className: 'mono small' },
            { data: 'leads', className: 'num mono' },
            { data: 'score', className: 'num mono' },
            { data: 'status', render: function (s) {
                return '<span class="badge ' + (cls[s] || 'badge-muted') + '">' + s + '</span>';
            }},
            { data: null, className: 'text-end', render: function (row) {
                return '<a class="btn btn-sm btn-outline-primary" href="' + row.detail_url + '">{{ __('Review') }}</a>';
            }},
        ],
        pageLength: 25,
        language: { emptyTable: '{{ __('No dealers registered yet.') }}' },
    });

    $('#filter-status').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
