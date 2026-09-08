@extends('layouts.dealer')

@section('title', __('Dealer dashboard — Krishi Junction'))

@section('content')
@if ($dealer->verification_status !== 'verified')
    <div class="alert alert-warning small">
        <b>{{ __('Verification pending.') }}</b>
        {{ __('Leads start arriving once our team verifies your documents. We will tell you as soon as that happens.') }}
        @if ($dealer->verification_remarks)
            <div class="mt-1">{{ __('Note from our team:') }} {{ $dealer->verification_remarks }}</div>
        @endif
    </div>
@endif

<div class="row g-3 mb-3">
    @foreach ([
        __('Unanswered leads') => [$stats['unanswered'], $stats['unanswered'] > 0],
        __('Leads this month') => [$stats['this_month'], false],
        __('Contacted') => [$stats['contacted'], false],
        __('Converted') => [$stats['converted'], false],
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
                <h2 class="h6 mb-0">{{ __('Recent leads') }}</h2>
                <a href="{{ route('dealer.leads.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Open inbox') }}</a>
            </div>

            @forelse ($recentLeads as $lead)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div>
                        <a href="{{ route('dealer.leads.show', $lead) }}" class="text-decoration-none fw-semibold small">
                            {{ $lead->name }}
                        </a>
                        <div class="small text-muted-2 mono">{{ $lead->reference_no }}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge badge-muted">{{ ucfirst($lead->status) }}</span>
                        <div class="small text-muted-2">{{ $lead->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">
                    {{ $dealer->verification_status === 'verified'
                        ? __('No leads yet. They arrive as buyers enquire about your brands in your district.')
                        : __('Leads begin after verification.') }}
                </p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-5">
        <div class="kj-panel p-4">
            <h2 class="h6 mb-2">{{ __('Your plan') }}</h2>

            <div class="d-flex justify-content-between align-items-baseline">
                <b>{{ $usage['plan'] ?? __('No active plan') }}</b>
                <span class="small text-muted-2 mono">
                    {{ $usage['used'] }}{{ $usage['limit'] ? ' / '.$usage['limit'] : '' }} {{ __('this month') }}
                </span>
            </div>

            @if ($usage['limit'])
                @php $percent = min(100, round($usage['used'] / max($usage['limit'], 1) * 100)); @endphp
                <div class="progress mt-2" style="height: 6px;" role="progressbar"
                     aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $percent }}%; background: var(--kj-green-700);"></div>
                </div>
            @endif

            <div class="d-flex justify-content-between small mt-3">
                <span class="text-muted-2">{{ __('Today') }}</span>
                <span class="mono">{{ $usage['daily_used'] }}{{ $usage['daily_cap'] ? ' / '.$usage['daily_cap'] : '' }}</span>
            </div>
            <div class="d-flex justify-content-between small">
                <span class="text-muted-2">{{ __('Response score') }}</span>
                <span class="mono">{{ number_format((float) $dealer->response_score, 1) }} / 10</span>
            </div>

            <p class="form-text mb-0 mt-2">
                {{ __('Answering quickly raises your score, and a higher score puts you first in line for the next lead.') }}
            </p>
        </div>

        <div class="kj-panel p-4 mt-3">
            <h2 class="h6 mb-2">{{ __('Your listings') }}</h2>
            <div class="d-flex justify-content-between small">
                <span class="text-muted-2">{{ __('New machinery in stock') }}</span>
                <span class="mono">{{ $stats['inventory'] }}</span>
            </div>
            <div class="d-flex justify-content-between small">
                <span class="text-muted-2">{{ __('Used listings live') }}</span>
                <span class="mono">{{ $stats['listings'] }}</span>
            </div>
            <a href="{{ route('dealer.inventory.index') }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                {{ __('Manage inventory') }}
            </a>
        </div>
    </div>
</div>
@endsection
