@extends('layouts.admin')

@section('title', __('Menus — Krishi Junction Admin'))
@section('page_title', __('Menus'))

@section('content')
@if (! $menu)
    <div class="kj-panel p-5 text-center">
        <p class="fw-semibold mb-1">{{ __('No menus defined') }}</p>
        <p class="small text-muted-2 mb-0">{{ __('Run the content seeder to create the header and footer menus.') }}</p>
    </div>
@else
    <div class="d-flex gap-2 flex-wrap mb-3">
        @foreach ($menus as $item)
            <a href="{{ route('admin.menus.index', $item->slug) }}"
               class="btn btn-sm {{ $menu->id === $item->id ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $item->name }}</a>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ $menu->name }}</h2>

                <div id="menu-items">
                    @forelse ($items as $item)
                        <div class="border rounded p-2 mb-2 js-item" data-id="{{ $item->id }}">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm js-label"
                                           value="{{ $item->label }}" aria-label="{{ __('Label') }}">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control form-control-sm mono js-url"
                                           value="{{ $item->url }}" aria-label="{{ __('Address') }}">
                                </div>
                                <div class="col-md-3 d-flex gap-1 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary js-save"
                                            data-url="{{ route('admin.menus.items.update', $item) }}">{{ __('Save') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary js-remove"
                                            data-url="{{ route('admin.menus.items.destroy', $item) }}"
                                            style="color: var(--kj-danger); border-color: var(--kj-danger);">×</button>
                                </div>
                            </div>

                            @foreach ($item->children as $child)
                                <div class="row g-2 align-items-center mt-1 ps-4">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm js-label"
                                               value="{{ $child->label }}" aria-label="{{ __('Label') }}">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control form-control-sm mono js-url"
                                               value="{{ $child->url }}" aria-label="{{ __('Address') }}">
                                    </div>
                                    <div class="col-md-3 d-flex gap-1 justify-content-end js-child" data-id="{{ $child->id }}">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-save"
                                                data-url="{{ route('admin.menus.items.update', $child) }}">{{ __('Save') }}</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-remove"
                                                data-url="{{ route('admin.menus.items.destroy', $child) }}"
                                                style="color: var(--kj-danger); border-color: var(--kj-danger);">×</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="small text-muted-2">{{ __('This menu is empty.') }}</p>
                    @endforelse
                </div>
            </div></div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Add an item') }}</h2>

                <label class="form-label" for="new-label">{{ __('Label') }}</label>
                <input type="text" id="new-label" class="form-control form-control-sm mb-2" maxlength="100">

                <label class="form-label" for="new-url">{{ __('Address') }}</label>
                <input type="text" id="new-url" class="form-control form-control-sm mono mb-2"
                       placeholder="/tractors" maxlength="255">

                <label class="form-label" for="new-parent">{{ __('Under') }}</label>
                <select id="new-parent" class="form-select form-select-sm mb-3">
                    <option value="">{{ __('Top level') }}</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @endforeach
                </select>

                <button type="button" class="btn btn-primary btn-sm w-100" id="add-item">{{ __('Add item') }}</button>
                <p class="small text-muted-2 mt-2 mb-0">
                    {{ __('Menus go two levels deep. A third level cannot be tapped on a phone.') }}
                </p>
            </div></div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if ($menu)
<script>
$(function () {
    $('#add-item').on('click', async function () {
        var response = await KJ.request({
            url: '{{ route('admin.menus.items.store', $menu) }}',
            method: 'POST',
            data: {
                label: $('#new-label').val(),
                url: $('#new-url').val(),
                parent_id: $('#new-parent').val() || null,
            },
        });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            return setTimeout(function () { window.location.reload(); }, 500);
        }

        KJ.toast(response.message, 'danger');
    });

    $('.js-save').on('click', async function () {
        var row = $(this).closest('.row').length ? $(this).closest('.row') : $(this).closest('.js-item');

        var response = await KJ.request({
            url: $(this).data('url'),
            method: 'POST',
            data: {
                _method: 'PUT',
                label: row.find('.js-label').val(),
                url: row.find('.js-url').val(),
                is_active: 1,
            },
        });

        KJ.toast(response.message, response.status === 'ok' ? 'success' : 'danger');
    });

    $('.js-remove').on('click', async function () {
        if (!window.confirm('{{ __('Remove this item and anything under it?') }}')) { return; }

        var response = await KJ.request({
            url: $(this).data('url'), method: 'POST', data: { _method: 'DELETE' },
        });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            return setTimeout(function () { window.location.reload(); }, 400);
        }

        KJ.toast(response.message, 'danger');
    });
});
</script>
@endif
@endpush
