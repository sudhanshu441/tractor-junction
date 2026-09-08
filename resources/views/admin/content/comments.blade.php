@extends('layouts.admin')

@section('title', __('Comments — Krishi Junction Admin'))
@section('page_title', __('Comments'))

@section('content')
<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $value => $label)
        <a href="{{ route('admin.comments.index', ['status' => $value]) }}"
           class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
            @if ($counts[$value]) <span class="badge badge-muted">{{ $counts[$value] }}</span> @endif
        </a>
    @endforeach
</div>

@forelse ($comments as $comment)
    <div class="card mb-2"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <b class="small">{{ $comment->user?->name ?? __('Reader') }}</b>
                <span class="small text-muted-2">
                    · {{ $comment->created_at->diffForHumans() }} ·
                    <a href="{{ route('blogs.show', $comment->blog->slug) }}" target="_blank" rel="noopener">
                        {{ Str::limit($comment->blog->title, 50) }}
                    </a>
                </span>
                <p class="small mb-0 mt-1">{{ $comment->body }}</p>
            </div>

            @if ($comment->status === 'pending')
                <div class="d-flex gap-2 js-row" data-url="{{ route('admin.comments.moderate', $comment) }}">
                    <button type="button" class="btn btn-sm btn-primary js-moderate" data-status="approved">{{ __('Publish') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-moderate" data-status="rejected"
                            style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Reject') }}</button>
                </div>
            @endif
        </div>
    </div></div>
@empty
    <div class="kj-panel p-5 text-center">
        <span class="badge badge-ok mb-2">{{ __('Nothing waiting') }}</span>
        <p class="small text-muted-2 mb-0">{{ __('No :status comments.', ['status' => $status]) }}</p>
    </div>
@endforelse

<div class="mt-3">{{ $comments->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-moderate').on('click', async function () {
        var row = $(this).closest('.js-row');
        row.find('button').prop('disabled', true);

        var response = await KJ.request({
            url: row.data('url'), method: 'POST', data: { status: $(this).data('status') },
        });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            return row.closest('.card').fadeOut(300, function () { $(this).remove(); });
        }

        row.find('button').prop('disabled', false);
        KJ.toast(response.message, 'danger');
    });
});
</script>
@endpush
