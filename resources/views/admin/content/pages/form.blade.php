@extends('layouts.admin')

@section('title', __('Page — Krishi Junction Admin'))
@section('page_title', $page->exists ? __('Edit page') : __('New page'))

@section('content')
<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf
    @if ($page->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body">
                <label class="form-label" for="title">{{ __('Title') }} <span style="color: var(--kj-danger);">*</span></label>
                <input type="text" id="title" name="title" class="form-control mb-3"
                       value="{{ old('title', $page->title) }}" maxlength="200" required>

                <label class="form-label" for="content">{{ __('Content') }}</label>
                <textarea id="content" name="content" class="form-control mono" rows="22"
                          style="font-size:.8125rem;">{{ old('content', $page->content) }}</textarea>
                <p class="small text-muted-2 mb-0">{{ __('HTML is allowed.') }}</p>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Search appearance') }}</h2>

                <label class="form-label" for="meta_title">{{ __('Meta title') }}</label>
                <input type="text" id="meta_title" name="meta_title" class="form-control mb-3"
                       value="{{ old('meta_title', $seo?->meta_title) }}" maxlength="200">

                <label class="form-label" for="meta_description">{{ __('Meta description') }}</label>
                <textarea id="meta_description" name="meta_description" class="form-control mb-3" rows="2"
                          maxlength="400">{{ old('meta_description', $seo?->meta_description) }}</textarea>

                <label class="form-label" for="robots">{{ __('Robots') }}</label>
                <select id="robots" name="robots" class="form-select">
                    @foreach (['index,follow', 'noindex,follow', 'noindex,nofollow'] as $value)
                        <option value="{{ $value }}" @selected(old('robots', $seo?->robots ?? 'index,follow') === $value)>{{ $value }}</option>
                    @endforeach
                </select>
                <p class="small text-muted-2 mt-1 mb-0">{{ __('Set a thank-you or policy page to noindex if it should not appear in search.') }}</p>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-3">{{ __('Save page') }}</button>

                <label class="form-label" for="slug">{{ __('Address') }}</label>
                <div class="input-group mb-1">
                    <span class="input-group-text mono">/</span>
                    <input type="text" id="slug" name="slug" class="form-control mono"
                           value="{{ old('slug', $page->slug) }}" maxlength="220">
                </div>
                <p class="small text-muted-2">{{ __('Website addresses like /tractors are reserved and will be refused.') }}</p>

                <label class="form-label" for="template">{{ __('Template') }}</label>
                <select id="template" name="template" class="form-select mb-3">
                    @foreach (['default' => __('Default')] as $value => $label)
                        <option value="{{ $value }}" @selected(old('template', $page->template) === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="sort_order">{{ __('Sort order') }}</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control mb-3" min="0"
                       value="{{ old('sort_order', $page->sort_order ?? 0) }}">

                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $page->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Live on the website') }}</label>
                </div>

                <div class="form-check">
                    <input type="hidden" name="show_in_footer" value="0">
                    <input class="form-check-input" type="checkbox" id="show_in_footer" name="show_in_footer" value="1"
                           @checked(old('show_in_footer', $page->show_in_footer))>
                    <label class="form-check-label" for="show_in_footer">{{ __('Link from the footer') }}</label>
                </div>
            </div></div>
        </div>
    </div>
</form>

@if ($page->exists)
    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="mt-3"
          onsubmit="return confirm('{{ __('Delete this page?') }}')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-secondary"
                style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Delete page') }}</button>
    </form>
@endif
@endsection
