@extends('layouts.panel')

@section('title', __('My account — Krishi Junction'))
@section('panel_name', __('My account'))

@section('panel_nav')
    <li><a class="nav-link active" href="{{ route('account.dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.listings.index') }}">{{ __('My listings') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.leads') }}">{{ __('Buyers') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.enquiries') }}">{{ __('My enquiries') }}</a></li>
    @foreach ([__('Loan applications'), __('Wishlist'), __('Saved searches'), __('Profile')] as $item)
        <li><a class="nav-link disabled text-muted-2" href="#">{{ $item }}</a></li>
    @endforeach
@endsection

@section('content')
<div class="kj-panel p-4 mb-3">
    <h1 class="h5 mb-1">{{ __('Welcome, :name', ['name' => $user->name]) }}</h1>
    <p class="text-muted-2 small mb-0">
        {{ $user->mobile }}
        @if ($user->hasVerifiedMobile())<span class="badge badge-ok ms-1">{{ __('Verified') }}</span>@endif
        · {{ __('member since :date', ['date' => $user->created_at->format('M Y')]) }}
    </p>
</div>

<div class="row g-3 mb-3">
    @foreach ([
        __('Live listings') => [$stats['live'], false],
        __('Awaiting review') => [$stats['pending'], $stats['pending'] > 0],
        __('Buyers received') => [$stats['buyers'], false],
        __('Enquiries sent') => [$stats['enquiries'], false],
    ] as $label => [$value, $alert])
        <div class="col-6 col-lg-3">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div>
                <div class="v">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="kj-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">{{ __('Your listings') }}</h2>
                <a href="{{ route('sell.start') }}" class="btn btn-sm btn-deep">{{ __('List a machine') }}</a>
            </div>

            @forelse ($recentListings as $listing)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div>
                        <a href="{{ route('account.listings.show', $listing) }}" class="text-decoration-none fw-semibold small">
                            {{ $listing->title ?: __('Draft listing') }}
                        </a>
                        <div class="small text-muted-2 mono">
                            {{ $listing->reference_no }} · ₹{{ number_format((float) $listing->expected_price) }}
                        </div>
                    </div>
                    <span class="badge {{ $listing->status === 'live' ? 'badge-ok' : 'badge-muted' }}">
                        {{ ucfirst($listing->status) }}
                    </span>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">
                    {{ __('Nothing listed yet. Selling is free and takes about three minutes.') }}
                </p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-5">
        <div class="kj-panel p-4">
            <h2 class="h6 mb-3">{{ __('Recent buyers') }}</h2>

            @forelse ($recentLeads as $lead)
                <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                    <div>
                        <b class="small">{{ $lead->name }}</b>
                        <div class="small text-muted-2 mono">{{ $lead->mobile }}</div>
                    </div>
                    <span class="small text-muted-2 text-nowrap">{{ $lead->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('No buyers have contacted you yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
