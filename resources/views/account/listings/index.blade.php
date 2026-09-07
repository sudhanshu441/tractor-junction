@extends('layouts.panel')

@section('title', __('My listings — Krishi Junction'))
@section('panel_name', __('My account'))
@section('panel_nav')
    <li><a class="nav-link" href="{{ route('account.dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li><a class="nav-link active" href="{{ route('account.listings.index') }}">{{ __('My listings') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.leads') }}">{{ __('Buyers') }}</a></li>
    <li><a class="nav-link" href="{{ route('account.enquiries') }}">{{ __('My enquiries') }}</a></li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h5 mb-0">{{ __('My listings') }}</h1>
    <a href="{{ route('sell.start') }}" class="btn btn-deep btn-sm">{{ __('List another machine') }}</a>
</div>

@forelse ($listings as $listing)
    @php
        $statusClass = match ($listing->status) {
            'live' => 'badge-ok', 'pending' => 'badge-warn', 'sold' => 'badge-info',
            'rejected', 'blocked' => 'badge-bad', default => 'badge-muted',
        };
    @endphp
    <div class="kj-panel p-3 mb-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <div class="ratio kj-thumb rounded" style="--bs-aspect-ratio: 75%;">
                    @if ($listing->primary_image)
                        <img src="{{ $listing->primary_image->thumbnailUrl() }}" alt="{{ $listing->title }}" class="object-fit-cover rounded">
                    @else
                        <span class="d-flex align-items-center justify-content-center">@include('partials.machine-icon')</span>
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <div class="d-flex gap-2 align-items-center flex-wrap mb-1">
                    <span class="badge {{ $statusClass }}">{{ ucfirst($listing->status) }}</span>
                    @if ($listing->is_verified)<span class="badge badge-ok">{{ __('Inspected') }}</span>@endif
                    <span class="small text-muted-2 mono">{{ $listing->reference_no }}</span>
                </div>
                <h2 class="h6 mb-1">
                    <a href="{{ route('account.listings.show', $listing) }}" class="text-decoration-none text-reset">
                        {{ $listing->title ?: __('Draft listing') }}
                    </a>
                </h2>
                <p class="small text-muted-2 mb-0">
                    ₹{{ number_format((float) $listing->expected_price) }}
                    · {{ $listing->city?->name }}
                    @if ($listing->expires_at && $listing->status === 'live')
                        · {{ __('expires :when', ['when' => $listing->expires_at->diffForHumans()]) }}
                    @endif
                </p>

                @if ($listing->rejection_reason && in_array($listing->status, ['rejected', 'pending']))
                    <div class="alert alert-warning small mt-2 mb-0">
                        <b>{{ __('Our team said:') }}</b> {{ $listing->rejection_reason }}
                    </div>
                @endif
            </div>

            <div class="col-md-2">
                <div class="d-flex gap-3">
                    <div><div class="kj-label">{{ __('Views') }}</div><div class="mono">{{ $listing->view_count }}</div></div>
                    <div><div class="kj-label">{{ __('Buyers') }}</div><div class="mono">{{ $listing->lead_count }}</div></div>
                </div>
            </div>

            <div class="col-md-2 text-md-end">
                @if ($listing->status === 'live')
                    <button class="btn btn-sm btn-outline-primary w-100 mb-1 js-sold"
                            data-url="{{ route('account.listings.sold', $listing) }}">{{ __('Mark sold') }}</button>
                    @if ($listing->isExpiringSoon())
                        <button class="btn btn-sm btn-primary w-100 js-renew"
                                data-url="{{ route('account.listings.renew', $listing) }}">{{ __('Renew') }}</button>
                    @endif
                @elseif ($listing->status === 'expired')
                    <button class="btn btn-sm btn-primary w-100 js-renew"
                            data-url="{{ route('account.listings.renew', $listing) }}">{{ __('Repost') }}</button>
                @endif
                <a href="{{ route('account.listings.show', $listing) }}"
                   class="btn btn-sm btn-link w-100">{{ __('Details') }}</a>
            </div>
        </div>
    </div>
@empty
    <div class="kj-panel p-5 text-center">
        <p class="fw-semibold mb-1">{{ __('You have not listed anything yet') }}</p>
        <p class="small text-muted-2 mb-3">{{ __('Listing is free and takes about three minutes.') }}</p>
        <a href="{{ route('sell.start') }}" class="btn btn-primary">{{ __('Sell your machine') }}</a>
    </div>
@endforelse

<div class="mt-3">{{ $listings->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-sold').on('click', function () {
        if (!confirm('{{ __('Mark this as sold? It will be removed from search.') }}')) { return; }
        KJ.request({ url: $(this).data('url'), method: 'POST' }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });

    $('.js-renew').on('click', function () {
        KJ.request({ url: $(this).data('url'), method: 'POST' }).then(function (res) {
            KJ.toast(res.message, 'success');
            window.location.reload();
        });
    });
});
</script>
@endpush
