@extends('layouts.dealer')

@section('title', __('Leads — Krishi Junction dealer panel'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h5 mb-1">{{ __('Your leads') }}</h1>
        <p class="small text-muted-2 mb-0">
            {{ __('Every buyer here verified their mobile number. Answer within :minutes minutes or the lead moves on.', ['minutes' => $slaMinutes]) }}
        </p>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="{{ route('dealer.leads.index') }}"
       class="btn btn-sm {{ ! $status ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('All') }}</a>
    @foreach ($counts as $key => $count)
        <a href="{{ route('dealer.leads.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ ucfirst($key) }} <span class="text-muted-2">{{ $count }}</span>
        </a>
    @endforeach
</div>

<div class="kj-panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Reference') }}</th><th>{{ __('Buyer') }}</th><th>{{ __('Interested in') }}</th>
                    <th>{{ __('Location') }}</th><th>{{ __('Time left') }}</th><th>{{ __('Status') }}</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($leads as $lead)
                @php
                    $assignment = $lead->currentAssignment;
                    $minutesLeft = $assignment && $assignment->status === 'pending'
                        ? $slaMinutes - (int) $assignment->assigned_at->diffInMinutes(now())
                        : null;
                @endphp
                <tr>
                    <td class="mono small">{{ $lead->reference_no }}</td>
                    <td>
                        <b class="small">{{ $lead->name }}</b>
                        <div class="small text-muted-2 mono">{{ $lead->mobile }}</div>
                    </td>
                    <td class="small">
                        @if ($lead->leadable instanceof \App\Models\Product)
                            {{ $lead->leadable->full_name }}
                        @elseif ($lead->leadable instanceof \App\Models\UsedListing)
                            {{ $lead->leadable->title }}
                        @else
                            {{ ucfirst(str_replace('_', ' ', $lead->type)) }}
                        @endif
                    </td>
                    <td class="small">{{ $lead->district?->name ?? '—' }}</td>
                    <td>
                        @if ($minutesLeft === null)
                            <span class="small text-muted-2">—</span>
                        @elseif ($minutesLeft > 0)
                            <span class="badge {{ $minutesLeft < 30 ? 'badge-bad' : 'badge-warn' }} mono">
                                {{ $minutesLeft }}m
                            </span>
                        @else
                            <span class="badge badge-bad">{{ __('Overdue') }}</span>
                        @endif
                    </td>
                    <td><span class="badge badge-muted">{{ ucfirst($lead->status) }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('dealer.leads.show', $lead) }}" class="btn btn-sm btn-outline-primary">
                            {{ __('Open') }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center small text-muted-2 py-4">{{ __('No leads in this view.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $leads->links() }}</div>
@endsection
