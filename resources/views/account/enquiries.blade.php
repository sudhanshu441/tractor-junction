@extends('layouts.panel')

@section('title', __('My enquiries — Krishi Junction'))
@section('panel_name', __('My account'))
@section('panel_nav')
    <li><a class="nav-link" href="{{ route('account.dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.listings.index') }}">{{ __('My listings') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.leads') }}">{{ __('Buyers') }}</a></li>
    <li><a class="nav-link active" href="{{ route('account.enquiries') }}">{{ __('My enquiries') }}</a></li>
@endsection

@section('content')
<h1 class="h5 mb-1">{{ __('Enquiries you sent') }}</h1>
<p class="small text-muted-2 mb-3">{{ __('Machinery and dealers you asked about.') }}</p>

<div class="kj-panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>{{ __('Reference') }}</th><th>{{ __('About') }}</th><th>{{ __('Handled by') }}</th>
                    <th>{{ __('When') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
            @forelse ($leads as $lead)
                <tr>
                    <td class="mono small">{{ $lead->reference_no }}</td>
                    <td class="small">
                        @if ($lead->leadable instanceof \App\Models\Product)
                            {{ $lead->leadable->full_name }}
                        @elseif ($lead->leadable instanceof \App\Models\UsedListing)
                            {{ $lead->leadable->title }}
                        @else
                            {{ ucfirst(str_replace('_', ' ', $lead->type)) }}
                        @endif
                    </td>
                    <td class="small">{{ $lead->currentAssignment?->dealer?->display_name ?? __('Our team') }}</td>
                    <td class="small text-muted-2">{{ $lead->created_at->diffForHumans() }}</td>
                    <td><span class="badge badge-muted">{{ ucfirst($lead->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center small text-muted-2 py-4">{{ __('You have not sent any enquiries yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $leads->links() }}</div>
@endsection
