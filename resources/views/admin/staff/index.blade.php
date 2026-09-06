@extends('layouts.admin')

@section('title', __('Staff users — Krishi Junction Admin'))
@section('page_title', __('Staff users'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <p class="text-muted-2 small mb-0">{{ __('Internal users with access to the admin panel.') }}</p>
    </div>
    <div class="d-flex gap-2">
        <select id="filter-role" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All roles') }}</option>
            @foreach ($roles as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        @can('users.create')
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary btn-sm">{{ __('Add staff user') }}</a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="staff-table" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Mobile') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('Last login') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    var table = $('#staff-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.staff.data') }}',
            type: 'POST',
            data: function (d) { d.role_id = $('#filter-role').val(); },
        },
        columns: [
            { data: 'name' },
            { data: 'email' },
            { data: 'mobile', className: 'mono small' },
            { data: 'roles', orderable: false },
            { data: 'last_login_at', className: 'small text-muted-2' },
            {
                data: 'is_active',
                orderable: false,
                render: function (active) {
                    return active
                        ? '<span class="badge badge-ok">{{ __('Active') }}</span>'
                        : '<span class="badge badge-bad">{{ __('Blocked') }}</span>';
                },
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function (row) {
                    return '<a class="btn btn-sm btn-outline-primary" href="' + row.edit_url + '">{{ __('Edit') }}</a> ' +
                        '<button class="btn btn-sm btn-outline-secondary js-toggle" data-url="' + row.toggle_url + '">' +
                        (row.is_active ? '{{ __('Block') }}' : '{{ __('Activate') }}') + '</button>';
                },
            },
        ],
        pageLength: 25,
        order: [[0, 'asc']],
        language: { emptyTable: '{{ __('No staff users yet.') }}' },
    });

    $('#filter-role').on('change', function () { table.ajax.reload(); });

    $('#staff-table').on('click', '.js-toggle', function () {
        var btn = $(this);

        KJ.request({ url: btn.data('url'), method: 'POST' }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { table.ajax.reload(null, false); }
        });
    });
});
</script>
@endpush
