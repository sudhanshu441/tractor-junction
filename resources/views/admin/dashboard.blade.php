@extends('layouts.admin')

@section('title', __('Dashboard — Krishi Junction Admin'))
@section('page_title', __('Dashboard'))

@section('content')
<div class="row g-3 mb-4">
    @foreach ([
        __('Total users') => $stats['users'],
        __('Customers') => $stats['customers'],
        __('Staff') => $stats['staff'],
        __('Brands') => $stats['brands'],
        __('States') => $stats['states'],
        __('Districts') => $stats['districts'],
    ] as $label => $value)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kj-stat">
                <div class="k">{{ $label }}</div>
                <div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="alert alert-secondary d-flex gap-3 align-items-start">
    <span class="badge badge-info">{{ __('Phase 1') }}</span>
    <div class="small">
        <b>{{ __('Foundation is live.') }}</b>
        {{ __('Schema, roles, geography, settings, notification templates, OTP login and the admin shell are in place. Catalogue, marketplace, dealer, lead and finance modules land in phases 2-5 and appear in the sidebar as they ship.') }}
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('Recent users') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>{{ __('Name') }}</th><th>{{ __('Mobile') }}</th><th>{{ __('Type') }}</th><th>{{ __('Joined') }}</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($recentUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td class="mono small">{{ $user->masked_mobile }}</td>
                                <td><span class="badge badge-muted">{{ $user->user_type }}</span></td>
                                <td class="small text-muted-2">{{ $user->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted-2 small">{{ __('No users yet.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('Recent activity') }}</h2>
                @forelse ($recentActivity as $activity)
                    <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                        <span class="small">
                            {{ $activity->description }}
                            @if ($activity->causer)<span class="text-muted-2">— {{ $activity->causer->name }}</span>@endif
                        </span>
                        <span class="small text-muted-2 mono text-nowrap">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="small text-muted-2 mb-0">{{ __('Nothing logged yet. Every create, update and delete on business data is recorded here.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
