@extends('layouts.admin')

@section('title', __('Products — Krishi Junction Admin'))
@section('page_title', __('Products'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="text-muted-2 small mb-0">{{ __('Tractors, implements, harvesters, tyres and farm tools.') }}</p>
    <div class="d-flex gap-2 flex-wrap">
        <select id="filter-brand" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All brands') }}</option>
            @foreach ($brands as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>
        <select id="filter-category" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>
        <select id="filter-status" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('Any status') }}</option>
            @foreach (['available', 'upcoming', 'discontinued'] as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @can('products.create')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">{{ __('Add product') }}</a>
        @endcan
    </div>
</div>

<div class="card"><div class="card-body">
    <table id="product-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th>{{ __('Model') }}</th>
                <th>{{ __('Brand') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('HP') }}</th>
                <th>{{ __('Price') }}</th>
                <th class="num">{{ __('Views') }}</th>
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
    var table = $('#product-table').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route('admin.products.data') }}', type: 'POST',
            data: function (d) {
                d.brand_id = $('#filter-brand').val();
                d.category_id = $('#filter-category').val();
                d.status = $('#filter-status').val();
            },
        },
        columns: [
            { data: null, render: function (row) {
                var specs = '<span class="badge ' + (row.specs > 0 ? 'badge-ok' : 'badge-warn') + '">' +
                    row.specs + ' specs</span>';
                return '<b>' + row.name + '</b> ' + specs;
            }},
            { data: 'brand' },
            { data: 'category' },
            { data: 'hp', className: 'mono small' },
            { data: 'price', className: 'small' },
            { data: 'views', className: 'num mono' },
            { data: 'status', orderable: false, render: function (status, t, row) {
                if (!row.is_active) { return '<span class="badge badge-muted">{{ __('Draft') }}</span>'; }
                var cls = status === 'available' ? 'badge-ok' : (status === 'upcoming' ? 'badge-info' : 'badge-muted');
                return '<span class="badge ' + cls + '">' + status + '</span>';
            }},
            { data: null, orderable: false, searchable: false, className: 'text-end', render: function (row) {
                var html = '<a class="btn btn-sm btn-outline-primary" href="' + row.edit_url + '">{{ __('Edit') }}</a> ';
                html += '<button class="btn btn-sm btn-outline-secondary js-toggle" data-url="' + row.toggle_url + '">' +
                    (row.is_active ? '{{ __('Unpublish') }}' : '{{ __('Publish') }}') + '</button>';
                return html;
            }},
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: { emptyTable: '{{ __('No products yet. Add the first model.') }}' },
    });

    $('#filter-brand, #filter-category, #filter-status').on('change', function () { table.ajax.reload(); });

    $('#product-table').on('click', '.js-toggle', function () {
        KJ.request({ url: $(this).data('url'), method: 'POST' }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            table.ajax.reload(null, false);
        });
    });
});
</script>
@endpush
