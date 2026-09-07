@extends('layouts.admin')

@section('title', __('Leads — Krishi Junction Admin'))
@section('page_title', __('Leads'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('All leads') => ['all', $counts['all'], false],
        __('Unassigned') => ['unassigned', $counts['unassigned'], $counts['unassigned'] > 0],
        __('Follow-up due today') => ['due_today', $counts['due_today'], $counts['due_today'] > 0],
        __('Duplicates') => ['duplicates', $counts['duplicates'], false],
    ] as $label => [$key, $value, $alert])
        <div class="col-6 col-lg-3">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div>
                <div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filter-type" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All types') }}</option>
            @foreach (['new_product', 'used_listing', 'dealer', 'loan', 'insurance', 'callback', 'contact'] as $type)
                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>

        <select id="filter-status" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('Any status') }}</option>
            @foreach (['new', 'assigned', 'contacted', 'qualified', 'converted', 'lost', 'duplicate', 'invalid'] as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
        </select>

        <select id="filter-state" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All states') }}</option>
            @foreach ($states as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>

        <div class="form-check form-switch align-self-center ms-2">
            <input class="form-check-input" type="checkbox" id="filter-unassigned">
            <label class="form-check-label small" for="filter-unassigned">{{ __('Unassigned only') }}</label>
        </div>
    </div>

    <div class="d-flex gap-2">
        @can('leads.assign')
            <button class="btn btn-outline-primary btn-sm" id="bulk-route" disabled>{{ __('Route selected') }}</button>
        @endcan
        @can('leads.export')
            <a href="{{ route('admin.leads.export') }}" class="btn btn-outline-primary btn-sm">{{ __('Export CSV') }}</a>
        @endcan
    </div>
</div>

@cannot('leads.view_contact')
    <div class="alert alert-secondary small">
        {{ __('Contact numbers are masked for your role. Ask an administrator for the leads.view_contact permission if you need them.') }}
    </div>
@endcannot

<div class="card"><div class="card-body">
    <table id="lead-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th style="width:26px;"><input type="checkbox" class="form-check-input" id="check-all"></th>
                <th>{{ __('Reference') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Contact') }}</th>
                <th>{{ __('About') }}</th>
                <th>{{ __('District') }}</th>
                <th>{{ __('Age') }}</th>
                <th>{{ __('Assignee') }}</th>
                <th>{{ __('Status') }}</th>
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
    var statusClass = {
        new: 'badge-warn', assigned: 'badge-info', contacted: 'badge-info',
        qualified: 'badge-ok', converted: 'badge-ok', lost: 'badge-bad',
        duplicate: 'badge-muted', invalid: 'badge-muted',
    };

    var table = $('#lead-table').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route('admin.leads.data') }}', type: 'POST',
            data: function (d) {
                d.type = $('#filter-type').val();
                d.status = $('#filter-status').val();
                d.state_id = $('#filter-state').val();
                d.unassigned = $('#filter-unassigned').is(':checked') ? 1 : 0;
            },
        },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: function (id) {
                return '<input type="checkbox" class="form-check-input js-row" value="' + id + '">';
            }},
            { data: 'reference', render: function (ref, t, row) {
                return '<a class="mono small" href="' + row.detail_url + '">' + ref + '</a>';
            }},
            { data: 'type', render: function (type) {
                return '<span class="badge badge-muted">' + type.replace(/_/g, ' ') + '</span>';
            }},
            { data: null, render: function (row) {
                return '<b>' + row.name + '</b><div class="small text-muted-2 mono">' + (row.mobile || '—') + '</div>';
            }},
            { data: 'about', className: 'small' },
            { data: 'district', className: 'small' },
            { data: 'age', className: 'small text-muted-2' },
            { data: 'assignee', className: 'small' },
            { data: 'status', render: function (status) {
                return '<span class="badge ' + (statusClass[status] || 'badge-muted') + '">' + status + '</span>';
            }},
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        language: { emptyTable: '{{ __('No leads yet.') }}' },
    });

    $('#filter-type, #filter-status, #filter-state, #filter-unassigned').on('change', function () {
        table.ajax.reload();
    });

    $('#check-all').on('change', function () {
        $('.js-row').prop('checked', $(this).is(':checked')).trigger('change');
    });

    $('#lead-table').on('change', '.js-row', function () {
        $('#bulk-route').prop('disabled', $('.js-row:checked').length === 0);
    });

    $('#bulk-route').on('click', function () {
        var ids = $('.js-row:checked').map(function () { return this.value; }).get();

        KJ.request({ url: '{{ route('admin.leads.bulk-route') }}', method: 'POST', data: { lead_ids: ids } })
            .then(function (res) {
                KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
                table.ajax.reload(null, false);
                $('#bulk-route').prop('disabled', true);
            });
    });
});
</script>
@endpush
