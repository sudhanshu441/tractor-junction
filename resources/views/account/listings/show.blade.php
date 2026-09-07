@extends('layouts.panel')

@section('title', __('Listing :ref — Krishi Junction', ['ref' => $listing->reference_no]))
@section('panel_name', __('My account'))
@section('panel_nav')
    <li><a class="nav-link" href="{{ route('account.dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li><a class="nav-link active" href="{{ route('account.listings.index') }}">{{ __('My listings') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.leads') }}">{{ __('Buyers') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.enquiries') }}">{{ __('My enquiries') }}</a></li>
@endsection

@section('content')
<div class="kj-panel p-4 mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="h5 mb-1">{{ $listing->title ?: __('Draft listing') }}</h1>
            <p class="small text-muted-2 mb-0 mono">{{ $listing->reference_no }}</p>
        </div>
        <span class="badge {{ $listing->status === 'live' ? 'badge-ok' : 'badge-muted' }}">{{ ucfirst($listing->status) }}</span>
    </div>

    <div class="row g-3 mt-2">
        @foreach ([
            __('Asking price') => '₹'.number_format((float) $listing->expected_price),
            __('Views') => number_format($listing->view_count),
            __('Buyers') => number_format($listing->lead_count),
            __('Expires') => $listing->expires_at?->format('d M Y') ?? '—',
        ] as $label => $value)
            <div class="col-6 col-md-3">
                <div class="kj-stat"><div class="k">{{ $label }}</div><div class="v" style="font-size:1.1rem;">{{ $value }}</div></div>
            </div>
        @endforeach
    </div>

    @if ($valuation)
        <div class="alert alert-secondary small mt-3 mb-0">
            {{ __('Similar machines sell for ₹:min – ₹:max. Buyers compare against this band.', [
                'min' => number_format($valuation['min']), 'max' => number_format($valuation['max']),
            ]) }}
        </div>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="kj-panel p-4">
            <h2 class="h6 mb-3">{{ __('Buyers who contacted you') }}</h2>

            @forelse ($leads as $lead)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div>
                        <b class="small">{{ $lead->name }}</b>
                        <div class="small text-muted-2 mono">{{ $lead->mobile }}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge badge-muted">{{ ucfirst($lead->status) }}</span>
                        <div class="small text-muted-2">{{ $lead->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">
                    {{ __('No buyers yet. Listings with clear photos and a fair price get contacted soonest.') }}
                </p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-5">
        <div class="kj-panel p-4">
            <h2 class="h6 mb-3">{{ __('What has happened') }}</h2>

            @foreach ($listing->statusLogs as $log)
                <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                    <div class="small">
                        <span class="mono text-muted-2">{{ $log->from_status }} &rarr; {{ $log->to_status }}</span>
                        @if ($log->remarks)<div>{{ $log->remarks }}</div>@endif
                    </div>
                    <span class="small text-muted-2 text-nowrap">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
