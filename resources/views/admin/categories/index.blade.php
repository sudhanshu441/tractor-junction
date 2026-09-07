@extends('layouts.admin')

@section('title', __('Categories — Krishi Junction Admin'))
@section('page_title', __('Categories'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="text-muted-2 small mb-0">
        {{ __('Categories decide which specification fields a product form asks for.') }}
    </p>
    @can('categories.create')
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">{{ __('Add category') }}</a>
    @endcan
</div>

@foreach ($categories->groupBy('type') as $type => $group)
    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 text-capitalize mb-3">{{ str_replace('_', ' ', $type) }}</h2>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Parent') }}</th>
                        <th class="num">{{ __('Products') }}</th>
                        <th class="num">{{ __('Order') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($group->sortBy('sort_order') as $category)
                    <tr>
                        <td>
                            <b>{{ $category->name }}</b>
                            <div class="small text-muted-2 mono">{{ $category->slug }}</div>
                        </td>
                        <td class="small text-muted-2">{{ $categories->firstWhere('id', $category->parent_id)?->name ?? '—' }}</td>
                        <td class="num mono">{{ $category->products_count }}</td>
                        <td class="num mono">{{ $category->sort_order }}</td>
                        <td>
                            <span class="badge {{ $category->is_active ? 'badge-ok' : 'badge-muted' }}">
                                {{ $category->is_active ? __('Active') : __('Hidden') }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('categories.edit')
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
@endforeach

@if ($categories->isEmpty())
    <div class="kj-panel p-5 text-center">
        <p class="fw-semibold mb-1">{{ __('No categories yet') }}</p>
        <p class="small text-muted-2 mb-0">{{ __('Run the catalogue seeder or add the first category.') }}</p>
    </div>
@endif
@endsection
