@extends('layouts.admin')

@section('title', ($brand->exists ? __('Edit brand') : __('Add brand')).' — Krishi Junction Admin')
@section('page_title', $brand->exists ? __('Edit :name', ['name' => $brand->name]) : __('Add brand'))

@section('content')
<form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if ($brand->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">{{ __('Brand name') }}</label>
                        <input type="text" id="name" name="name" required maxlength="100"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $brand->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($brand->exists)
                            <div class="form-text">{{ __('URL: /tractors/brand/:slug — fixed once published.', ['slug' => $brand->slug]) }}</div>
                        @endif
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="country">{{ __('Country') }}</label>
                        <input type="text" id="country" name="country" class="form-control"
                               value="{{ old('country', $brand->country) }}" placeholder="India">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="founded_year">{{ __('Founded') }}</label>
                        <input type="number" id="founded_year" name="founded_year" min="1800" max="{{ date('Y') }}"
                               class="form-control @error('founded_year') is-invalid @enderror"
                               value="{{ old('founded_year', $brand->founded_year) }}">
                        @error('founded_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="website">{{ __('Website') }}</label>
                        <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror"
                               value="{{ old('website', $brand->website) }}" placeholder="https://">
                        @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="logo">{{ __('Logo') }}</label>
                        <input type="file" id="logo" name="logo" accept="image/*"
                               class="form-control @error('logo') is-invalid @enderror">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($brand->logo)
                            <div class="form-text">{{ __('Current: :file', ['file' => basename($brand->logo)]) }}</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">{{ __('Description') }}</label>
                        <textarea id="description" name="description" rows="5" class="form-control"
                                  placeholder="{{ __('Shown on the brand landing page and used for SEO.') }}">{{ old('description', $brand->description) }}</textarea>
                    </div>
                </div>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Visibility') }}</h2>

                <label class="form-label" for="sort_order">{{ __('Sort order') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0" class="form-control mb-3"
                       value="{{ old('sort_order', $brand->sort_order ?? 0) }}">

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_popular" name="is_popular"
                           value="1" @checked(old('is_popular', $brand->is_popular))>
                    <label class="form-check-label" for="is_popular">{{ __('Show in "popular brands"') }}</label>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active"
                           value="1" @checked(old('is_active', $brand->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>

                <hr>
                <button class="btn btn-primary w-100" type="submit">
                    {{ $brand->exists ? __('Save brand') : __('Create brand') }}
                </button>
                <a href="{{ route('admin.brands.index') }}" class="btn btn-link w-100 mt-1">{{ __('Cancel') }}</a>
            </div></div>
        </div>
    </div>
</form>
@endsection
