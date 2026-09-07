@extends('layouts.admin')

@section('title', __('Brands — Krishi Junction Admin'))
@section('page_title', __('Brands'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="text-muted-2 small mb-0">{{ __('Manufacturers whose machinery appears in the catalogue.') }}</p>
    @can('brands.create')
        <a href="{{ route('admin.brands.create') }}" class="btn btn-primary btn-sm">{{ __('Add brand') }}</a>
    @endcan
</div>

<div class="card"><div class="card-body">
    <table id="brand-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th>{{ __('Brand') }}</th>
                <th class="num">{{ __('Products') }}</th>
                <th class="num">{{ __('Order') }}</th>
                <th>{{ __('Popular') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
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
    var table = $('#brand-table').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '{{ route('admin.brands.data') }}', type: 'POST' },
        columns: [
            { data: null, render: function (row) {
                return '<b>' + row.name + '</b><div class="small text-muted-2 mono">' + row.slug + '</div>';
            }},
            { data: 'products_count', className: 'num' },
            { data: 'sort_order', className: 'num' },
            { data: 'is_popular', orderable: false, render: function (v) {
                return v ? '<span class="badge badge-ok">{{ __('Popular') }}</span>' : '<span class="text-muted-2 small">—</span>';
            }},
            { data: 'is_active', orderable: false, render: function (v) {
                return v ? '<span class="badge badge-ok">{{ __('Active') }}</span>'
                         : '<span class="badge badge-muted">{{ __('Hidden') }}</span>';
            }},
            { data: null, orderable: false, searchable: false, className: 'text-end', render: function (row) {
                return '<a class="btn btn-sm btn-outline-primary" href="' + row.edit_url + '">{{ __('Edit') }}</a> ' +
                    '<button class="btn btn-sm btn-outline-secondary js-toggle" data-url="' + row.toggle_url + '">' +
                    (row.is_active ? '{{ __('Hide') }}' : '{{ __('Show') }}') + '</button>';
            }},
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        language: { emptyTable: '{{ __('No brands yet. Add the first one.') }}' },
    });

    $('#brand-table').on('click', '.js-toggle', function () {
        KJ.request({ url: $(this).data('url'), method: 'POST' }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            table.ajax.reload(null, false);
        });
    });
});
</script>
@endpush
