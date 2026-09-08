@extends('layouts.app')

@section('title', __('Promote your listing — Krishi Junction'))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('account.listings.index') }}">{{ __('My listings') }}</a>
        <span class="text-muted-2">/</span>
        <a href="{{ route('account.listings.show', $listing) }}">{{ $listing->title }}</a>
        <span class="text-muted-2">/ {{ __('Promote') }}</span>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <h1 class="h5 mb-2">{{ __('Get this listing seen first') }}</h1>
            <p class="text-muted-2" style="max-width: 60ch;">
                {{ __('A promoted listing sits at the top of the matching search results and carries a highlight, so buyers scrolling a district page see it before the rest.') }}
            </p>

            @if ($running)
                <div class="alert alert-secondary small">
                    {{ __('This listing is promoted until :date under the :plan package.', [
                        'date' => $running->ends_at->format('d M Y'),
                        'plan' => $running->plan?->name ?? __('current'),
                    ]) }}
                </div>
            @elseif ($listing->status !== 'live')
                <div class="alert alert-warning small">
                    {{ __('Only a live listing can be promoted. This one is :status — get it approved first.', ['status' => $listing->status]) }}
                </div>
            @endif

            <div class="row g-3 mt-1">
                @forelse ($plans as $plan)
                    <div class="col-md-6">
                        <div class="kj-panel p-4 h-100 d-flex flex-column">
                            <div class="kj-label mb-1">{{ $plan->name }}</div>
                            <div class="h4 mono mb-2">
                                @if ((float) $plan->price > 0)
                                    ₹{{ number_format((float) $plan->price) }}
                                @else
                                    {{ __('Free') }}
                                @endif
                            </div>
                            <ul class="small ps-3 mb-3">
                                @foreach ((array) ($plan->features ?? []) as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-auto">
                                <button type="button" class="btn btn-primary w-100 js-buy-plan"
                                        data-url="{{ route('account.boost.checkout', [$listing, $plan]) }}"
                                        data-name="{{ $plan->name }}"
                                        @disabled($running || $listing->status !== 'live')>
                                    {{ __('Promote') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="small text-muted-2">{{ __('No promotion packages are on sale right now.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-5">
            <div class="kj-panel p-3">
                <div class="kj-label mb-2">{{ __('The listing') }}</div>
                @if ($listing->images->isNotEmpty())
                    <img src="{{ $listing->images->first()->thumbnailUrl() }}" alt="{{ $listing->title }}"
                         class="img-fluid rounded mb-2">
                @endif
                <b class="d-block">{{ $listing->title }}</b>
                <p class="small text-muted-2 mb-0 mono">{{ $listing->reference_no }}</p>
                <p class="mono mt-2 mb-0">₹{{ number_format((float) $listing->expected_price) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/checkout.js') }}"></script>
<script>
$(function () {
    KJ.initCheckout('.js-buy-plan', '{{ __('Krishi Junction listing promotion') }}');
});
</script>
@endpush
