@extends('layouts.dealer')

@section('title', __('Plan & billing — Krishi Junction'))

@section('content')
<div class="kj-panel p-4 mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <div class="kj-label mb-1">{{ __('Current plan') }}</div>
            <h1 class="h5 mb-1">{{ $subscription?->plan?->name ?? __('No active plan') }}</h1>
            @if ($subscription)
                <p class="small text-muted-2 mb-0">
                    {{ __('Runs to :date', ['date' => $subscription->ends_at->format('d M Y')]) }}
                    @if ($subscription->status !== 'active')
                        · <span class="badge badge-warn">{{ str_replace('_', ' ', $subscription->status) }}</span>
                    @endif
                </p>
            @endif
        </div>

        <div class="row g-2">
            <div class="col-auto">
                <div class="kj-stat">
                    <div class="k">{{ __('Leads this month') }}</div>
                    <div class="v">{{ $usage['used'] }}<span class="small text-muted-2">/{{ $usage['limit'] ?: '∞' }}</span></div>
                </div>
            </div>
            <div class="col-auto">
                <div class="kj-stat">
                    <div class="k">{{ __('Today') }}</div>
                    <div class="v">{{ $usage['daily_used'] }}<span class="small text-muted-2">/{{ $usage['daily_cap'] ?: '∞' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<h2 class="h6 mb-3">{{ __('Plans') }}</h2>
<div class="row g-3">
    @foreach ($plans as $plan)
        @php $isCurrent = $subscription?->plan_id === $plan->id && $subscription->status === 'active'; @endphp
        <div class="col-md-4">
            <div class="kj-panel p-4 h-100 d-flex flex-column {{ $isCurrent ? 'border-2' : '' }}"
                 @if ($isCurrent) style="border-color: var(--kj-green-700);" @endif>
                <div class="kj-label mb-1">{{ $plan->name }}</div>
                <div class="h4 mb-1 mono">
                    @if ((float) $plan->price > 0)
                        ₹{{ number_format((float) $plan->price) }}
                        <span class="small text-muted-2">/ {{ str_replace('_', ' ', $plan->billing_cycle) }}</span>
                    @else
                        {{ __('Free') }}
                    @endif
                </div>

                <ul class="small ps-3 mt-2 mb-3">
                    <li>{{ $plan->lead_limit ? __(':n leads a month', ['n' => $plan->lead_limit]) : __('Unlimited leads') }}</li>
                    <li>{{ $plan->daily_lead_cap ? __(':n leads a day', ['n' => $plan->daily_lead_cap]) : __('No daily cap') }}</li>
                    <li>{{ $plan->inventory_limit ? __(':n models in your inventory', ['n' => $plan->inventory_limit]) : __('Unlimited inventory') }}</li>
                    @foreach ((array) ($plan->features ?? []) as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>

                <div class="mt-auto">
                    @if ($isCurrent)
                        <button class="btn btn-outline-secondary w-100" disabled>{{ __('Your current plan') }}</button>
                    @else
                        <button type="button" class="btn btn-primary w-100 js-buy-plan"
                                data-url="{{ route('dealer.billing.checkout', $plan) }}"
                                data-name="{{ $plan->name }}">
                            {{ (float) $plan->price > 0 ? __('Upgrade') : __('Switch to free') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@if ($payments->isNotEmpty())
    <h2 class="h6 mt-4 mb-2">{{ __('Payment history') }}</h2>
    <div class="kj-panel table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>{{ __('Reference') }}</th><th class="num">{{ __('Amount') }}</th>
                    <th>{{ __('Status') }}</th><th>{{ __('Date') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td class="mono small">{{ $payment->reference_no }}</td>
                        <td class="num mono">₹{{ number_format((float) $payment->amount) }}</td>
                        <td>
                            <span class="badge {{ $payment->status === 'paid' ? 'badge-ok' : ($payment->status === 'failed' ? 'badge-bad' : 'badge-muted') }}">
                                {{ $payment->status }}
                            </span>
                        </td>
                        <td class="small text-muted-2">{{ $payment->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('assets/js/checkout.js') }}"></script>
<script>
$(function () {
    KJ.initCheckout('.js-buy-plan', '{{ __('Krishi Junction dealer plan') }}');
});
</script>
@endpush
