@extends('layouts.admin')

@section('title', __('Used listings — Krishi Junction Admin'))
@section('page_title', __('Used listings'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <select id="filter-status" class="form-select form-select-sm" style="width:auto;">
        <option value="">{{ __('Any status') }}</option>
        @foreach (['draft', 'pending', 'live', 'sold', 'expired', 'rejected', 'blocked'] as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.listings.queue') }}" class="btn btn-primary btn-sm">{{ __('Open moderation queue') }}</a>
        <a href="{{ route('admin.listings.reports') }}" class="btn btn-outline-primary btn-sm">{{ __('Reported listings') }}</a>
    </div>
</div>

<div class="card"><div class="card-body">
    <table id="listing-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th>{{ __('Reference') }}</th><th>{{ __('Listing') }}</th><th>{{ __('Seller') }}</th>
                <th>{{ __('City') }}</th><th>{{ __('Price') }}</th>
                <th class="num">{{ __('Views') }}</th><th class="num">{{ __('Leads') }}</th>
                <th>{{ __('Status') }}</th><th>{{ __('Created') }}</th>
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
    var cls = { live: 'badge-ok', pending: 'badge-warn', sold: 'badge-info',
        rejected: 'badge-bad', blocked: 'badge-bad', expired: 'badge-muted', draft: 'badge-muted' };

    var table = $('#listing-table').DataTable({
        processing: true, serverSide: true, ordering: false,
        ajax: {
            url: '{{ route('admin.listings.data') }}', type: 'POST',
            data: function (d) { d.status = $('#filter-status').val(); },
        },
        columns: [
            { data: 'reference', className: 'mono small' },
            { data: 'title' },
            { data: null, render: function (row) {
                return row.seller + '<div class="small text-muted-2 mono">' + (row.mobile || '—') + '</div>';
            }},
            { data: 'city', className: 'small' },
            { data: 'price', className: 'mono small' },
            { data: 'views', className: 'num mono' },
            { data: 'leads', className: 'num mono' },
            { data: 'status', render: function (s) {
                return '<span class="badge ' + (cls[s] || 'badge-muted') + '">' + s + '</span>';
            }},
            { data: 'created', className: 'small text-muted-2' },
        ],
        pageLength: 25,
        language: { emptyTable: '{{ __('No used listings yet.') }}' },
    });

    $('#filter-status').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
