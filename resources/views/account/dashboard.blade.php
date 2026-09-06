@extends('layouts.panel')

@section('title', __('My account — Krishi Junction'))
@section('panel_name', __('My account'))

@section('panel_nav')
    <li><a class="nav-link active" href="{{ route('account.dashboard') }}">{{ __('Dashboard') }}</a></li>
    @foreach ([__('My listings'), __('Enquiries'), __('Loan applications'), __('Wishlist'), __('Saved searches'), __('Profile')] as $item)
        <li><a class="nav-link disabled text-muted-2" href="#">{{ $item }}</a></li>
    @endforeach
@endsection

@section('content')
<div class="kj-panel p-4">
    <h1 class="h5 mb-1">{{ __('Welcome, :name', ['name' => $user->name]) }}</h1>
    <p class="text-muted-2 small mb-4">
        {{ __('Mobile :mobile · joined :joined', ['mobile' => $user->mobile, 'joined' => $user->created_at->format('d M Y')]) }}
        @if ($user->hasVerifiedMobile())
            <span class="badge badge-ok ms-1">{{ __('Mobile verified') }}</span>
        @endif
    </p>

    <div class="row g-3">
        @foreach ([
            __('My listings') => 0, __('Enquiries received') => 0,
            __('Enquiries sent') => 0, __('Loan applications') => 0,
        ] as $label => $value)
            <div class="col-6 col-lg-3">
                <div class="kj-stat">
                    <div class="k">{{ $label }}</div>
                    <div class="v">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-secondary small mt-4 mb-0">
        {{ __('Listings, enquiries and loan tracking become active in phases 3 and 4.') }}
    </div>
</div>
@endsection
