@extends('layouts.admin')

@section('title', __('Reviews — Krishi Junction Admin'))
@section('page_title', __('Reviews'))

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('Pending') => [$counts['pending'], $counts['pending'] > 0],
        __('Approved') => [$counts['approved'], false],
        __('Rejected') => [$counts['rejected'], false],
    ] as $label => [$value, $alert])
        <div class="col-12 col-md-4">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $value => $label)
        <a href="{{ route('admin.reviews.index', ['status' => $value]) }}"
           class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
    @endforeach
</div>

@forelse ($reviews as $review)
    @php
        $subject = $review->reviewable;
        $subjectName = $subject?->name ?? $subject?->display_name ?? $subject?->business_name ?? __('subject removed');
        $subjectKind = $subject instanceof \App\Models\Dealer ? __('Dealer') : __('Model');
    @endphp
    <div class="card mb-3" id="review-{{ $review->id }}"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <span class="badge badge-muted">{{ $subjectKind }}</span>
                <b class="ms-1">{{ $subjectName }}</b>
                @if ($review->is_verified_owner)
                    <span class="badge badge-ok">{{ __('verified owner') }}</span>
                @endif
                <div class="small text-muted-2 mt-1">
                    {{ $review->user?->name ?? __('deleted user') }} ·
                    {{ $review->created_at->diffForHumans() }}
                    @if ($review->ownership_duration)
                        · {{ __('owned :duration', ['duration' => str_replace('_', ' ', $review->ownership_duration)]) }}
                    @endif
                </div>
            </div>
            <div class="text-end">
                <div class="mono fs-5">{{ number_format((float) $review->rating, 1) }}<span class="text-muted-2 small">/5</span></div>
                <div class="small text-muted-2">{{ __(':up helpful · :down not', ['up' => $review->helpful_count, 'down' => $review->unhelpful_count]) }}</div>
            </div>
        </div>

        @if ($review->title)
            <p class="fw-semibold mt-3 mb-1">{{ $review->title }}</p>
        @endif
        <p class="small mb-2">{{ $review->body }}</p>

        <div class="row g-2">
            @if ($review->pros)
                <div class="col-md-6">
                    <div class="kj-label mb-1">{{ __('Pros') }}</div>
                    <p class="small mb-0">{{ $review->pros }}</p>
                </div>
            @endif
            @if ($review->cons)
                <div class="col-md-6">
                    <div class="kj-label mb-1">{{ __('Cons') }}</div>
                    <p class="small mb-0">{{ $review->cons }}</p>
                </div>
            @endif
        </div>

        @if ($review->aspectRatings->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach ($review->aspectRatings as $aspect)
                    <span class="badge badge-muted">
                        {{ ucfirst(str_replace('_', ' ', $aspect->aspect)) }} {{ $aspect->rating }}/5
                    </span>
                @endforeach
            </div>
        @endif

        @if ($review->status === 'rejected' && $review->rejection_reason)
            <div class="alert alert-secondary small mt-3 mb-0">
                {{ __('Rejected: :reason', ['reason' => $review->rejection_reason]) }}
            </div>
        @endif

        @can('reviews.approve')
            @if ($review->status === 'pending')
                <div class="row g-2 align-items-end mt-3 js-decision" data-url="{{ route('admin.reviews.moderate', $review) }}">
                    <div class="col-md-8">
                        <label class="form-label" for="reason-{{ $review->id }}">
                            {{ __('Reason — required to reject, the author sees it') }}
                        </label>
                        <input type="text" id="reason-{{ $review->id }}" class="form-control form-control-sm js-reason"
                               maxlength="500" placeholder="{{ __('e.g. Names a competitor’s dealership') }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-primary btn-sm js-moderate" data-status="approved">{{ __('Publish') }}</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-moderate" data-status="rejected"
                                style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Reject') }}</button>
                    </div>
                </div>
            @endif
        @endcan
    </div></div>
@empty
    <div class="kj-panel p-5 text-center">
        <span class="badge badge-ok mb-2">{{ __('Nothing here') }}</span>
        <p class="fw-semibold mb-1">{{ __('No :status reviews', ['status' => $status]) }}</p>
        <p class="small text-muted-2 mb-0">{{ __('Reviews written by owners land here for moderation before they appear publicly.') }}</p>
    </div>
@endforelse

<div class="mt-3">{{ $reviews->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-moderate').on('click', async function () {
        var row = $(this).closest('.js-decision');
        var decision = $(this).data('status');
        var reason = row.find('.js-reason').val();

        if (decision === 'rejected' && !reason) {
            return KJ.toast('{{ __('A reason is required — the author sees it.') }}', 'danger');
        }

        row.find('.js-moderate').prop('disabled', true);

        var response = await KJ.request({
            url: row.data('url'),
            method: 'POST',
            data: { status: decision, reason: reason },
        });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            row.closest('.card').fadeOut(300, function () { $(this).remove(); });
        } else {
            row.find('.js-moderate').prop('disabled', false);
            KJ.toast(response.message || '{{ __('Could not save that decision.') }}', 'danger');
        }
    });
});
</script>
@endpush
