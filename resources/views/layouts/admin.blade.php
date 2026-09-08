@extends('layouts.base')

@section('body_class', 'bg-body')

@section('body')
<div class="kj-admin">
    <aside class="kj-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 px-2 py-2 text-decoration-none">
            <img src="{{ asset('assets/brand/logo-mark.svg') }}" alt="" width="30" height="30">
            <span>
                <span class="d-block fw-bold" style="font-family:Archivo,sans-serif; letter-spacing:-.02em;">{{ __('Krishi Junction') }}</span>
                <span class="d-block mono text-muted-2" style="font-size:.625rem;">{{ __('ADMIN PANEL') }}</span>
            </span>
        </a>

        <ul class="nav flex-column mt-2">
            <li><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>

            <li class="nav-heading">{{ __('Catalogue') }}</li>
            @can('brands.view')
                <li><a class="nav-link {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}" href="{{ route('admin.brands.index') }}">{{ __('Brands') }}</a></li>
            @endcan
            @can('categories.view')
                <li><a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">{{ __('Categories') }}</a></li>
            @endcan
            @can('products.view')
                <li><a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">{{ __('Products') }}</a></li>
            @endcan
            @can('specs.view')
                <li><a class="nav-link {{ request()->routeIs('admin.specs.*') ? 'active' : '' }}" href="{{ route('admin.specs.index') }}">{{ __('Specifications') }}</a></li>
            @endcan

            <li class="nav-heading">{{ __('Marketplace') }}</li>
            @can('listings.view')
                <li><a class="nav-link {{ request()->routeIs('admin.listings.index') ? 'active' : '' }}" href="{{ route('admin.listings.index') }}">{{ __('Used listings') }}</a></li>
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.listings.queue') ? 'active' : '' }}" href="{{ route('admin.listings.queue') }}">
                        {{ __('Moderation') }}
                        @php $pending = \App\Models\UsedListing::pendingReview()->count(); @endphp
                        @if ($pending)<span class="badge badge-bad ms-auto">{{ $pending }}</span>@endif
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.listings.reports') ? 'active' : '' }}" href="{{ route('admin.listings.reports') }}">
                        {{ __('Reports') }}
                        @php $reports = \App\Models\ListingReport::open()->count(); @endphp
                        @if ($reports)<span class="badge badge-bad ms-auto">{{ $reports }}</span>@endif
                    </a>
                </li>
            @endcan
            @can('inspections.view')
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.inspections.*') ? 'active' : '' }}" href="{{ route('admin.inspections.index') }}">
                        {{ __('Inspections') }}
                        @php $openInspections = \App\Models\Inspection::open()->count(); @endphp
                        @if ($openInspections)<span class="badge badge-warn ms-auto">{{ $openInspections }}</span>@endif
                    </a>
                </li>
            @endcan
            @can('reviews.view')
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                        {{ __('Reviews') }}
                        @php $pendingReviews = \App\Models\Review::where('status', 'pending')->count(); @endphp
                        @if ($pendingReviews)<span class="badge badge-bad ms-auto">{{ $pendingReviews }}</span>@endif
                    </a>
                </li>
            @endcan

            <li class="nav-heading">{{ __('Network & demand') }}</li>
            @can('leads.view')
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}" href="{{ route('admin.leads.index') }}">
                        {{ __('Leads') }}
                        @php $unassigned = \App\Models\Lead::unassigned()->count(); @endphp
                        @if ($unassigned)<span class="badge badge-bad ms-auto">{{ $unassigned }}</span>@endif
                    </a>
                </li>
            @endcan
            @can('dealers.view')
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.dealers.*') ? 'active' : '' }}" href="{{ route('admin.dealers.index') }}">
                        {{ __('Dealers') }}
                        @php $pendingDealers = \App\Models\Dealer::where('verification_status', 'pending')->count(); @endphp
                        @if ($pendingDealers)<span class="badge badge-warn ms-auto">{{ $pendingDealers }}</span>@endif
                    </a>
                </li>
            @endcan
            @can('loans.view')
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.loans.*') ? 'active' : '' }}" href="{{ route('admin.loans.index') }}">
                        {{ __('Loan applications') }}
                        @php $openLoans = \App\Models\LoanApplication::whereIn('status', ['submitted', 'docs_pending'])->count(); @endphp
                        @if ($openLoans)<span class="badge badge-warn ms-auto">{{ $openLoans }}</span>@endif
                    </a>
                </li>
            @endcan

            <li class="nav-heading">{{ __('Access') }}</li>
            @can('users.view')
                <li><a class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}">{{ __('Staff users') }}</a></li>
            @endcan
            @can('roles.view')
                <li><a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">{{ __('Roles & permissions') }}</a></li>
            @endcan
        </ul>
    </aside>

    <div>
        <div class="kj-topbar">
            <h1 class="h6 mb-0 flex-grow-1">@yield('page_title', __('Dashboard'))</h1>
            <span class="badge badge-ok">{{ auth()->user()->getRoleNames()->first() }}</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">{{ auth()->user()->name }}</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('home') }}">{{ __('View website') }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item" type="submit">{{ __('Log out') }}</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <div class="kj-content">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@endsection
