@extends('layouts.admin')

@section('title', ($category->exists ? __('Edit category') : __('Add category')).' — Krishi Junction Admin')
@section('page_title', $category->exists ? __('Edit :name', ['name' => $category->name]) : __('Add category'))

@section('content')
<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
    @csrf
    @if ($category->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Details') }}</h2>

                <label class="form-label" for="name">{{ __('Name') }}</label>
                <input type="text" id="name" name="name" required class="form-control mb-3 @error('name') is-invalid @enderror"
                       value="{{ old('name', $category->name) }}">
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                <label class="form-label" for="type">{{ __('Machinery type') }}</label>
                <select id="type" name="type" class="form-select mb-3" required>
                    @foreach (['tractor', 'implement', 'harvester', 'tyre', 'farm_tool'] as $option)
                        <option value="{{ $option }}" @selected(old('type', $category->type) === $option)>
                            {{ ucfirst(str_replace('_', ' ', $option)) }}
                        </option>
                    @endforeach
                </select>

                <label class="form-label" for="parent_id">{{ __('Parent category') }}</label>
                <select id="parent_id" name="parent_id" class="form-select mb-3">
                    <option value="">{{ __('None — top level') }}</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>

                <label class="form-label" for="description">{{ __('Description') }}</label>
                <textarea id="description" name="description" rows="4" class="form-control mb-3">{{ old('description', $category->description) }}</textarea>

                <label class="form-label" for="sort_order">{{ __('Sort order') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0" class="form-control mb-3"
                       value="{{ old('sort_order', $category->sort_order ?? 0) }}">

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active"
                           value="1" @checked(old('is_active', $category->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>

                <hr>
                <button class="btn btn-primary w-100" type="submit">
                    {{ $category->exists ? __('Save category') : __('Create category') }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-link w-100 mt-1">{{ __('Cancel') }}</a>
            </div></div>
        </div>

        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-1">{{ __('Specification fields') }}</h2>
                <p class="small text-muted-2 mb-3">
                    {{ __('Tick the specifications a product in this category should be asked for. The product form is built from this list.') }}
                </p>

                @forelse ($attributes as $groupName => $groupAttributes)
                    <div class="kj-label mt-3 mb-2">{{ $groupName ?: __('Ungrouped') }}</div>
                    <div class="row g-1">
                        @foreach ($groupAttributes as $attribute)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="spec_attributes[]"
                                           value="{{ $attribute->id }}" id="attr-{{ $attribute->id }}"
                                           @checked(in_array($attribute->id, old('spec_attributes', $assigned)))>
                                    <label class="form-check-label small" for="attr-{{ $attribute->id }}">
                                        {{ $attribute->name }}
                                        @if ($attribute->unit)<span class="text-muted-2">({{ $attribute->unit }})</span>@endif
                                        @if ($attribute->is_filterable)<span class="badge badge-ok ms-1">{{ __('filter') }}</span>@endif
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="small text-muted-2">
                        {{ __('No specifications defined yet.') }}
                        <a href="{{ route('admin.specs.index') }}">{{ __('Add some first') }}</a>.
                    </p>
                @endforelse
            </div></div>
        </div>
    </div>
</form>
@endsection
