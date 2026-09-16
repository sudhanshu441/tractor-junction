@extends('layouts.admin')

@section('title', __('Visitors — Krishi Junction Admin'))
@section('page_title', __('Visitors & intent'))

@section('content')
<div class="alert alert-secondary small">
    {{ __('These are browsers, not people. A name and a number appear here only where somebody chose to leave them — a website cannot read a visitor\'s phone number, and nothing here tries to.') }}
</div>

<div class="row g-3 mb-3">
    @foreach ([
        __('Visitors') => $totals['visitors'],
        __('Callback requests') => $totals['callbacks'],
        __('Leads with a journey') => $totals['identified'],
    ] as $label => $value)
        <div class="col-12 col-md-4">
            <div class="kj-stat"><div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div></div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.visitors.index', ['days' => $days]) }}"
           class="btn btn-sm {{ $intent ? 'btn-outline-secondary' : 'btn-primary' }}">{{ __('All') }}</a>
        @foreach ($intents as $key => $count)
            <a href="{{ route('admin.visitors.index', ['days' => $days, 'intent' => $key]) }}"
               class="btn btn-sm {{ $intent === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ ucfirst($key) }} <span class="badge badge-muted">{{ $count }}</span>
            </a>
        @endforeach
    </div>
    <div class="d-flex gap-2">
        @foreach ([1 => __('Today'), 7 => __('7 days'), 30 => __('30 days')] as $value => $label)
            <a href="{{ route('admin.visitors.index', array_filter(['days' => $value, 'intent' => $intent])) }}"
               class="btn btn-sm {{ $days === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Visitor') }}</th><th>{{ __('Looking for') }}</th>
                    <th>{{ __('Machines viewed') }}</th><th class="num">{{ __('Pages') }}</th>
                    <th>{{ __('Last seen') }}</th><th>{{ __('Contact') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($visitors as $visitor)
                @php
                    $journey = $journeys[$visitor->visitor_id] ?? [];
                    $lead = $converted[$visitor->visitor_id] ?? null;
                @endphp
                <tr>
                    <td class="mono small text-muted-2">{{ Str::limit($visitor->visitor_id, 13, '…') }}</td>
                    <td>
                        @if ($journey['primary_intent'] ?? null)
                            <span class="badge badge-info">{{ ucfirst($journey['primary_intent']) }}</span>
                        @else
                            <span class="text-muted-2 small">—</span>
                        @endif
                        @if ($journey['brands_viewed'] ?? [])
                            <div class="small text-muted-2">{{ implode(', ', $journey['brands_viewed']) }}</div>
                        @endif
                    </td>
                    <td class="small">
                        @forelse (array_slice($journey['machines_viewed'] ?? [], 0, 3) as $machine)
                            <div>{{ $machine }}</div>
                        @empty
                            <span class="text-muted-2">{{ __('browsing') }}</span>
                        @endforelse
                    </td>
                    <td class="num mono small">{{ $visitor->pages }}</td>
                    <td class="small text-muted-2">
                        {{ \Illuminate\Support\Carbon::parse($visitor->last_seen)->diffForHumans() }}
                    </td>
                    <td>
                        @if ($lead)
                            <a href="{{ route('admin.leads.show', $lead) }}" class="small fw-semibold">
                                {{ $lead->name }}
                            </a>
                            <div class="small text-muted-2 mono">
                                {{ auth()->user()->can('leads.view_contact') ? $lead->mobile : $lead->masked_mobile }}
                            </div>
                        @else
                            <span class="badge badge-muted">{{ __('anonymous') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted-2 py-4">
                        {{ __('No visitors recorded in this period.') }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $visitors->links() }}</div>
@endsection
