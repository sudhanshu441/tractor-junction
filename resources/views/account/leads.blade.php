@extends('layouts.panel')

@section('title', __('Buyers — Krishi Junction'))
@section('panel_name', __('My account'))
@section('panel_nav')
    <li><a class="nav-link" href="{{ route('account.dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.listings.index') }}">{{ __('My listings') }}</a></li>
    <li><a class="nav-link active" href="{{ route('account.leads') }}">{{ __('Buyers') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.enquiries') }}">{{ __('My enquiries') }}</a></li>
@endsection

@section('content')
<h1 class="h5 mb-1">{{ __('Buyers who contacted you') }}</h1>
<p class="small text-muted-2 mb-3">{{ __('Every buyer here verified their mobile number before seeing yours.') }}</p>

<div class="kj-panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>{{ __('Buyer') }}</th><th>{{ __('Listing') }}</th><th>{{ __('When') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
            @forelse ($leads as $lead)
                <tr>
                    <td><b class="small">{{ $lead->name }}</b>
                        <div class="small text-muted-2 mono">{{ $lead->mobile }}</div></td>
                    <td class="small">{{ $lead->leadable?->title ?? '—' }}</td>
                    <td class="small text-muted-2">{{ $lead->created_at->diffForHumans() }}</td>
                    <td><span class="badge badge-muted">{{ ucfirst($lead->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center small text-muted-2 py-4">{{ __('No buyers yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $leads->links() }}</div>
@endsection
