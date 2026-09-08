@extends('layouts.dealer')

@section('title', __('Inventory — Krishi Junction dealer panel'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h5 mb-1">{{ __('Your inventory') }}</h1>
        <p class="small text-muted-2 mb-0">
            {{ __('Models you stock. Buyers see these on your dealer page.') }}
            @if ($limit)
                <span class="mono">{{ $used }} / {{ $limit }}</span>
            @endif
        </p>
    </div>
</div>

@if (session('error'))
    <div class="alert alert-warning small">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('dealer.inventory.store') }}" class="kj-panel p-3 mb-3">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="product_id">{{ __('Model') }}</label>
            <select id="product_id" name="product_id" class="form-select form-select-sm" required>
                <option value="">{{ __('Select a model') }}</option>
                @foreach ($options as $product)
                    <option value="{{ $product->id }}">{{ $product->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="availability">{{ __('Availability') }}</label>
            <select id="availability" name="availability" class="form-select form-select-sm">
                @foreach (['in_stock' => __('In stock'), 'on_order' => __('On order'), 'out_of_stock' => __('Out of stock')] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="quantity">{{ __('Quantity') }}</label>
            <input type="number" id="quantity" name="quantity" class="form-control form-control-sm" min="0" value="1">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="offer_price">{{ __('Your price (₹)') }}</label>
            <input type="number" id="offer_price" name="offer_price" class="form-control form-control-sm" min="0">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Add to inventory') }}</button>
        </div>
    </div>
</form>

<div class="kj-panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>{{ __('Model') }}</th><th>{{ __('Availability') }}</th>
                    <th class="num">{{ __('Qty') }}</th><th class="num">{{ __('Your price') }}</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($inventory as $item)
                <tr data-row="{{ $item->id }}">
                    <td>
                        <b class="small">{{ $item->product?->full_name }}</b>
                        @if ($item->branch)<div class="small text-muted-2">{{ $item->branch->name }}</div>@endif
                    </td>
                    <td>
                        <span class="badge {{ $item->availability === 'in_stock' ? 'badge-ok' : 'badge-muted' }}">
                            {{ str_replace('_', ' ', $item->availability) }}
                        </span>
                    </td>
                    <td class="num mono">{{ $item->quantity }}</td>
                    <td class="num mono">{{ $item->offer_price ? '₹'.number_format((float) $item->offer_price) : '—' }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary js-remove"
                                data-url="{{ route('dealer.inventory.destroy', $item) }}">{{ __('Remove') }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center small text-muted-2 py-4">
                    {{ __('Nothing in your inventory yet.') }}
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $inventory->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-remove').on('click', function () {
        var row = $(this).closest('tr');

        KJ.request({ url: $(this).data('url'), method: 'POST', data: { _method: 'DELETE' } })
            .then(function (res) {
                KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
                if (res.status === 'ok') { row.fadeOut(200, function () { $(this).remove(); }); }
            });
    });
});
</script>
@endpush
