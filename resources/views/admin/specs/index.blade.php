@extends('layouts.admin')

@section('title', __('Specifications — Krishi Junction Admin'))
@section('page_title', __('Specifications'))

@section('content')
<div class="alert alert-secondary small">
    <b>{{ __('This is the catalogue schema.') }}</b>
    {{ __('Adding a specification here makes it available to every category that opts in — no code change and no migration. Flag one as "filterable" to give it a facet on the listing pages.') }}
</div>

<div class="row g-3">
    <div class="col-lg-8">
        @forelse ($groups as $group)
            <div class="card mb-3"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">{{ $group->name }}</h2>
                    <span class="badge badge-muted mono">{{ $group->attributes->count() }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Specification') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Unit') }}</th>
                                <th>{{ __('Flags') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($group->attributes as $attribute)
                            <tr>
                                <td>
                                    <b>{{ $attribute->name }}</b>
                                    <div class="small text-muted-2 mono">{{ $attribute->slug }}</div>
                                </td>
                                <td class="small">{{ $attribute->data_type }}</td>
                                <td class="small mono">{{ $attribute->unit ?: '—' }}</td>
                                <td>
                                    @if ($attribute->is_filterable)<span class="badge badge-ok">{{ __('filter') }}</span>@endif
                                    @if ($attribute->is_key_spec)<span class="badge badge-info">{{ __('key') }}</span>@endif
                                    @if ($attribute->is_comparable)<span class="badge badge-muted">{{ __('compare') }}</span>@endif
                                </td>
                                <td class="text-end">
                                    @can('specs.delete')
                                        <form method="POST" action="{{ route('admin.specs.attributes.destroy', $attribute) }}"
                                              onsubmit="return confirm('{{ __('Delete this specification?') }}');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        @if ($group->attributes->isEmpty())
                            <tr><td colspan="5" class="small text-muted-2">{{ __('No specifications in this group yet.') }}</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div></div>
        @empty
            <div class="kj-panel p-5 text-center">
                <p class="fw-semibold mb-1">{{ __('No specification groups yet') }}</p>
                <p class="small text-muted-2 mb-0">{{ __('Create a group such as "Engine" to get started.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="col-lg-4">
        @can('specs.create')
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Add a group') }}</h2>
                <form method="POST" action="{{ route('admin.specs.groups.store') }}">
                    @csrf
                    <label class="form-label" for="group-name">{{ __('Group name') }}</label>
                    <input type="text" id="group-name" name="name" required class="form-control mb-2"
                           placeholder="{{ __('Engine, Transmission, Hydraulics…') }}">

                    <label class="form-label" for="group-sort">{{ __('Sort order') }}</label>
                    <input type="number" id="group-sort" name="sort_order" min="0" value="0" class="form-control mb-3">

                    <button class="btn btn-outline-primary w-100" type="submit">{{ __('Add group') }}</button>
                </form>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Add a specification') }}</h2>
                <form method="POST" action="{{ route('admin.specs.attributes.store') }}">
                    @csrf
                    <label class="form-label" for="spec_group_id">{{ __('Group') }}</label>
                    <select id="spec_group_id" name="spec_group_id" class="form-select mb-2" required>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>

                    <label class="form-label" for="attr-name">{{ __('Name') }}</label>
                    <input type="text" id="attr-name" name="name" required class="form-control mb-2"
                           placeholder="{{ __('No. of Cylinders') }}">

                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label" for="data_type">{{ __('Data type') }}</label>
                            <select id="data_type" name="data_type" class="form-select">
                                @foreach (['string', 'int', 'decimal', 'boolean', 'select', 'json'] as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label" for="unit">{{ __('Unit') }}</label>
                            <input type="text" id="unit" name="unit" class="form-control" placeholder="HP, cc, kg">
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_filterable" value="1" id="is_filterable">
                        <label class="form-check-label small" for="is_filterable">{{ __('Filterable — gets a facet') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_key_spec" value="1" id="is_key_spec">
                        <label class="form-check-label small" for="is_key_spec">{{ __('Key spec — shown in the summary strip') }}</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_comparable" value="1" id="is_comparable" checked>
                        <label class="form-check-label small" for="is_comparable">{{ __('Comparable') }}</label>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">{{ __('Add specification') }}</button>
                </form>
            </div></div>
        @endcan
    </div>
</div>
@endsection
