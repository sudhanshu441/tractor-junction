@extends('layouts.panel')

@section('title', __('Dealer panel — Krishi Junction'))
@section('panel_name', __('Dealer panel'))

@section('panel_nav')
    <li><a class="nav-link active" href="{{ route('dealer.dashboard') }}">{{ __('Dashboard') }}</a></li>
    @foreach ([__('Leads'), __('Inventory'), __('Used listings'), __('Branches'), __('Reviews'), __('Plan')] as $item)
        <li><a class="nav-link disabled text-muted-2" href="#">{{ $item }}</a></li>
    @endforeach
@endsection

@section('content')
<div class="kj-panel p-4">
    <h1 class="h5 mb-1">{{ $dealer?->display_name ?? __('Dealer panel') }}</h1>

    @if ($dealer)
        <p class="text-muted-2 small mb-4">
            <span class="mono">{{ $dealer->code }}</span> ·
            {{ $dealer->city?->name }} ·
            <span class="badge {{ $dealer->verification_status === 'verified' ? 'badge-ok' : 'badge-warn' }}">
                {{ ucfirst($dealer->verification_status) }}
            </span>
        </p>
    @else
        <div class="alert alert-warning small">
            {{ __('No dealer profile is linked to this account yet. An administrator links it during verification.') }}
        </div>
    @endif

    <div class="alert alert-secondary small mb-0">
        {{ __('The dealer panel — leads inbox, inventory and reports — is built in phase 4.') }}
    </div>
</div>
@endsection
