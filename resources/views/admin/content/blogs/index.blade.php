@extends('layouts.admin')

@section('title', __('Posts — Krishi Junction Admin'))
@section('page_title', __('News & guides'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('Published') => $counts['published'],
        __('Drafts') => $counts['draft'],
        __('Scheduled') => $counts['scheduled'],
    ] as $label => $value)
        <div class="col-4">
            <div class="kj-stat"><div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div></div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <select id="filter-status" class="form-select form-select-sm" style="width:auto;">
        <option value="">{{ __('Any status') }}</option>
        @foreach (['draft', 'scheduled', 'published', 'archived'] as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary btn-sm">{{ __('Write a post') }}</a>
</div>

<div class="card"><div class="card-body">
    <table id="post-table" class="table table-hover align-middle w-100">
        <thead>
            <tr>
                <th>{{ __('Title') }}</th><th>{{ __('Type') }}</th><th>{{ __('Category') }}</th>
                <th>{{ __('Author') }}</th><th>{{ __('Status') }}</th>
                <th class="num">{{ __('Views') }}</th><th>{{ __('Published') }}</th><th></th>
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
    var cls = { published: 'badge-ok', scheduled: 'badge-info', draft: 'badge-muted', archived: 'badge-muted' };

    var table = $('#post-table').DataTable({
        processing: true, serverSide: true, ordering: false,
        ajax: {
            url: '{{ route('admin.blogs.data') }}', type: 'POST',
            data: function (d) { d.status = $('#filter-status').val(); },
        },
        columns: [
            { data: 'title' },
            { data: 'type', className: 'small text-muted-2' },
            { data: 'category', className: 'small' },
            { data: 'author', className: 'small text-muted-2' },
            { data: 'status', render: function (s) {
                return '<span class="badge ' + (cls[s] || 'badge-muted') + '">' + s + '</span>';
            }},
            { data: 'views', className: 'num mono small' },
            { data: 'published', className: 'small text-muted-2' },
            { data: null, className: 'text-end text-nowrap', render: function (row) {
                var html = '<a class="btn btn-sm btn-outline-primary" href="' + row.edit_url + '">{{ __('Edit') }}</a>';
                if (row.view_url) {
                    html += ' <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="' + row.view_url + '">{{ __('View') }}</a>';
                }
                return html;
            }},
        ],
        pageLength: 25,
        language: { emptyTable: '{{ __('Nothing written yet.') }}' },
    });

    $('#filter-status').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
