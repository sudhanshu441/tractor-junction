@extends('layouts.admin')

@section('title', __('Reported listings — Krishi Junction Admin'))
@section('page_title', __('Reported listings'))

@section('content')
<p class="text-muted-2 small">{{ __('Listings buyers have flagged. Dismiss or block, then the report closes.') }}</p>

<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Listing') }}</th><th>{{ __('Reason') }}</th>
                    <th>{{ __('Details') }}</th><th>{{ __('Reported') }}</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($reports as $report)
                <tr data-report="{{ $report->id }}">
                    <td>
                        @if ($report->listing)
                            <a href="{{ route('used.show', $report->listing->slug) }}" target="_blank" rel="noopener"
                               class="text-decoration-none fw-semibold">{{ $report->listing->title }}</a>
                            <div class="small text-muted-2 mono">{{ $report->listing->reference_no }}</div>
                        @else
                            <span class="text-muted-2 small">{{ __('Listing deleted') }}</span>
                        @endif
                    </td>
                    <td><span class="badge badge-warn">{{ str_replace('_', ' ', $report->reason) }}</span></td>
                    <td class="small">{{ $report->details ?: '—' }}</td>
                    <td class="small text-muted-2">{{ $report->created_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        @can('listings.approve')
                            <button class="btn btn-sm btn-outline-secondary js-resolve" data-action="dismiss"
                                    data-url="{{ route('admin.listings.reports.resolve', $report) }}">{{ __('Dismiss') }}</button>
                            <button class="btn btn-sm btn-outline-secondary js-resolve" data-action="block_listing"
                                    data-url="{{ route('admin.listings.reports.resolve', $report) }}"
                                    style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Block listing') }}</button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center small text-muted-2 py-4">{{ __('No open reports.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $reports->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-resolve').on('click', function () {
        var $row = $(this).closest('tr');
        var action = $(this).data('action');

        if (action === 'block_listing' && !confirm('{{ __('Block this listing? The seller is told why.') }}')) { return; }

        KJ.request({ url: $(this).data('url'), method: 'POST', data: { action: action } })
            .then(function (res) {
                KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
                if (res.status === 'ok') { $row.fadeOut(200, function () { $(this).remove(); }); }
            });
    });
});
</script>
@endpush
