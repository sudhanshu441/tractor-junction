@extends('layouts.base')

@section('body')
    @include('partials.header')

    <main class="container-xl py-4">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="kj-panel p-3">
                    <div class="kj-label mb-1">{{ __('Dealer panel') }}</div>
                    @isset($dealer)
                        <div class="small fw-semibold">{{ $dealer->display_name }}</div>
                        <div class="small text-muted-2 mono mb-2">{{ $dealer->code }}</div>
                        <span class="badge {{ $dealer->verification_status === 'verified' ? 'badge-ok' : 'badge-warn' }}">
                            {{ ucfirst($dealer->verification_status) }}
                        </span>
                    @endisset

                    <ul class="nav flex-column small mt-3">
                        <li><a class="nav-link {{ request()->routeIs('dealer.dashboard') ? 'active' : '' }}" href="{{ route('dealer.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li><a class="nav-link {{ request()->routeIs('dealer.leads.*') ? 'active' : '' }}" href="{{ route('dealer.leads.index') }}">{{ __('Leads') }}</a></li>
                        <li><a class="nav-link {{ request()->routeIs('dealer.inventory.*') ? 'active' : '' }}" href="{{ route('dealer.inventory.index') }}">{{ __('Inventory') }}</a></li>
                        <li><a class="nav-link disabled text-muted-2" href="#">{{ __('Branches') }}</a></li>
                        @isset($dealer)
                            <li>
                                <a class="nav-link" href="{{ route('dealers.show', $dealer->slug) }}#reviews">
                                    {{ __('Reviews') }}
                                    @if ($dealer->rating_count)<span class="badge badge-muted">{{ $dealer->rating_count }}</span>@endif
                                </a>
                            </li>
                        @endisset
                        <li><a class="nav-link {{ request()->routeIs('dealer.billing.*') ? 'active' : '' }}" href="{{ route('dealer.billing.index') }}">{{ __('Plan & billing') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>

    @include('partials.footer')
@endsection
