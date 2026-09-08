@extends('layouts.admin')

@section('title', __('Messages — Krishi Junction Admin'))
@section('page_title', __('Contact messages'))

@section('content')
<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach (['new' => __('New'), 'read' => __('Read'), 'replied' => __('Replied'), 'closed' => __('Closed')] as $value => $label)
        <a href="{{ route('admin.messages.index', ['status' => $value]) }}"
           class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
            @if ($counts[$value]) <span class="badge badge-muted">{{ $counts[$value] }}</span> @endif
        </a>
    @endforeach
</div>

@forelse ($messages as $message)
    <div class="card mb-2"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="flex-grow-1">
                <b class="small">{{ $message->name }}</b>
                <span class="small text-muted-2">· {{ $message->created_at->diffForHumans() }}</span>
                @if ($message->subject)
                    <div class="fw-semibold small mt-1">{{ $message->subject }}</div>
                @endif
                <p class="small mb-1 mt-1">{{ $message->message }}</p>
                <div class="small text-muted-2 mono">
                    @if ($message->mobile)
                        <a href="tel:{{ $message->mobile }}">{{ auth()->user()->can('leads.view_contact') ? $message->mobile : substr($message->mobile, 0, 2).'XXXXXX'.substr($message->mobile, -2) }}</a>
                    @endif
                    @if ($message->email)
                        · <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                    @endif
                </div>
            </div>

            <div class="js-row" data-url="{{ route('admin.messages.update', $message) }}">
                <select class="form-select form-select-sm js-status" style="width: 9rem;"
                        aria-label="{{ __('Message status') }}">
                    @foreach (['new', 'read', 'replied', 'closed'] as $value)
                        <option value="{{ $value }}" @selected($message->status === $value)>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
                @if ($message->handler)
                    <div class="small text-muted-2 mt-1">{{ __('by :name', ['name' => $message->handler->name]) }}</div>
                @endif
            </div>
        </div>
    </div></div>
@empty
    <div class="kj-panel p-5 text-center">
        <span class="badge badge-ok mb-2">{{ __('Inbox clear') }}</span>
        <p class="small text-muted-2 mb-0">{{ __('No :status messages.', ['status' => $status]) }}</p>
    </div>
@endforelse

<div class="mt-3">{{ $messages->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-status').on('change', async function () {
        var row = $(this).closest('.js-row');

        var response = await KJ.request({
            url: row.data('url'), method: 'POST', data: { status: $(this).val() },
        });

        KJ.toast(response.message, response.status === 'ok' ? 'success' : 'danger');
    });
});
</script>
@endpush
