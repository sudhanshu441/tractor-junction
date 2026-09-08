@extends('layouts.admin')

@section('title', __('Pages — Krishi Junction Admin'))
@section('page_title', __('Pages'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="small text-muted-2 mb-0">
        {{ __('Static pages published at the root of the site, like /about-us.') }}
    </p>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">{{ __('Add page') }}</a>
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th><th>{{ __('Address') }}</th><th>{{ __('Template') }}</th>
                    <th>{{ __('In footer') }}</th><th>{{ __('Status') }}</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pages as $page)
                <tr>
                    <td>{{ $page->title }}</td>
                    <td class="mono small">/{{ $page->slug }}</td>
                    <td class="small text-muted-2">{{ $page->template }}</td>
                    <td><span class="badge {{ $page->show_in_footer ? 'badge-ok' : 'badge-muted' }}">
                        {{ $page->show_in_footer ? __('yes') : __('no') }}</span></td>
                    <td><span class="badge {{ $page->is_active ? 'badge-ok' : 'badge-muted' }}">
                        {{ $page->is_active ? __('live') : __('hidden') }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                        @if ($page->is_active)
                            <a href="{{ route('pages.show', $page->slug) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted-2 py-4">{{ __('No pages yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $pages->links() }}</div>
@endsection
