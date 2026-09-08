@extends('layouts.admin')

@section('title', __('Newsletter — Krishi Junction Admin'))
@section('page_title', __('Newsletter list'))

@section('content')
<div class="row g-3 mb-3">
    @foreach ([__('Subscribed') => $counts['active'], __('Unsubscribed') => $counts['unsubscribed']] as $label => $value)
        <div class="col-6">
            <div class="kj-stat"><div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div></div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.subscribers.export') }}" class="btn btn-outline-primary btn-sm">{{ __('Export CSV') }}</a>
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>{{ __('Email') }}</th><th>{{ __('Mobile') }}</th>
                    <th>{{ __('Status') }}</th><th>{{ __('Subscribed') }}</th></tr>
            </thead>
            <tbody>
            @forelse ($subscribers as $subscriber)
                <tr>
                    <td class="mono small">{{ $subscriber->email }}</td>
                    <td class="mono small">{{ $subscriber->mobile ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $subscriber->is_active ? 'badge-ok' : 'badge-muted' }}">
                            {{ $subscriber->is_active ? __('subscribed') : __('unsubscribed') }}
                        </span>
                    </td>
                    <td class="small text-muted-2">{{ $subscriber->created_at->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted-2 py-4">{{ __('Nobody has subscribed yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $subscribers->links() }}</div>
@endsection
