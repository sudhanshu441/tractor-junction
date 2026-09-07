@extends('layouts.admin')

@section('title', __('Prices — :name', ['name' => $product->full_name]))
@section('page_title', __('Prices — :name', ['name' => $product->full_name]))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="text-muted-2 small mb-0">
        {{ __('The national row is the fallback; a state row overrides it for visitors in that state. On-road is calculated, never typed.') }}
    </p>
    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">{{ __('Back to product') }}</a>
</div>

@if (session('import_errors'))
    <div class="alert alert-warning small">
        <b>{{ __('Some rows were skipped:') }}</b>
        <ul class="mb-0">@foreach (session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('Price matrix') }}</h2>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="price-table">
                    <thead>
                        <tr>
                            <th style="min-width:150px">{{ __('Scope') }}</th>
                            <th class="num">{{ __('Ex-showroom') }}</th>
                            <th class="num">{{ __('RTO') }}</th>
                            <th class="num">{{ __('Insurance') }}</th>
                            <th class="num">{{ __('Other') }}</th>
                            <th class="num">{{ __('On-road') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $national = $product->prices->firstWhere(fn ($p) => $p->state_id === null);
                            $byState = $product->prices->whereNotNull('state_id')->keyBy('state_id');
                        @endphp

                        <tr class="kj-wash js-price-row" data-state-id="">
                            <td><b>{{ __('National') }}</b><div class="small text-muted-2">{{ __('fallback') }}</div></td>
                            <td><input type="number" class="form-control form-control-sm num js-ex" value="{{ $national?->ex_showroom ? (int) $national->ex_showroom : '' }}"></td>
                            <td><input type="number" class="form-control form-control-sm num js-rto" value="{{ $national?->rto_charges ? (int) $national->rto_charges : '' }}"></td>
                            <td><input type="number" class="form-control form-control-sm num js-ins" value="{{ $national?->insurance_amount ? (int) $national->insurance_amount : '' }}"></td>
                            <td><input type="number" class="form-control form-control-sm num js-oth" value="{{ $national?->other_charges ? (int) $national->other_charges : '' }}"></td>
                            <td class="num mono js-onroad">{{ $national ? number_format((float) $national->on_road_price) : '—' }}</td>
                            <td><button type="button" class="btn btn-sm btn-primary js-save-price">{{ __('Save') }}</button></td>
                        </tr>

                        @foreach ($states as $state)
                            @php $row = $byState->get($state->id); @endphp
                            <tr class="js-price-row" data-state-id="{{ $state->id }}">
                                <td>{{ $state->name }} <span class="small text-muted-2 mono">{{ $state->code }}</span></td>
                                <td><input type="number" class="form-control form-control-sm num js-ex" value="{{ $row?->ex_showroom ? (int) $row->ex_showroom : '' }}"></td>
                                <td><input type="number" class="form-control form-control-sm num js-rto" value="{{ $row?->rto_charges ? (int) $row->rto_charges : '' }}"></td>
                                <td><input type="number" class="form-control form-control-sm num js-ins" value="{{ $row?->insurance_amount ? (int) $row->insurance_amount : '' }}"></td>
                                <td><input type="number" class="form-control form-control-sm num js-oth" value="{{ $row?->other_charges ? (int) $row->other_charges : '' }}"></td>
                                <td class="num mono js-onroad">{{ $row ? number_format((float) $row->on_road_price) : '—' }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary js-save-price">{{ __('Save') }}</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Bulk import') }}</h2>
            <p class="small text-muted-2">
                {{ __('CSV columns: product_slug, state_code, ex_showroom, rto, insurance, other. Leave state_code blank for the national row.') }}
            </p>
            <form method="POST" action="{{ route('admin.prices.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".csv" class="form-control form-control-sm mb-2" required>
                <button class="btn btn-outline-primary btn-sm w-100" type="submit">{{ __('Import prices') }}</button>
            </form>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Recent price changes') }}</h2>
            @forelse ($history as $entry)
                <div class="d-flex justify-content-between gap-2 py-2 border-bottom small">
                    <span>
                        {{ $entry->state?->name ?? __('National') }}
                        <div class="text-muted-2 mono">
                            {{ $entry->old_price ? number_format((float) $entry->old_price) : '—' }}
                            &rarr; {{ number_format((float) $entry->new_price) }}
                        </div>
                    </span>
                    <span class="text-muted-2 mono text-nowrap">{{ $entry->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('No price changes recorded yet.') }}</p>
            @endforelse
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#price-table').on('click', '.js-save-price', function () {
        var $row = $(this).closest('.js-price-row');
        var ex = $row.find('.js-ex').val();

        if (!ex) { return KJ.toast('{{ __('Enter an ex-showroom price first.') }}', 'warning'); }

        KJ.request({
            url: '{{ route('admin.prices.store', $product) }}',
            method: 'POST',
            data: {
                state_id: $row.data('state-id') || null,
                ex_showroom: ex,
                rto_charges: $row.find('.js-rto').val() || 0,
                insurance_amount: $row.find('.js-ins').val() || 0,
                other_charges: $row.find('.js-oth').val() || 0,
            },
        }).then(function (res) {
            if (res.status !== 'ok') {
                return KJ.toast(res.message || '{{ __('Could not save that price.') }}', 'danger');
            }
            $row.find('.js-onroad').text(new Intl.NumberFormat('en-IN').format(res.data.on_road_price));
            KJ.toast(res.message, 'success');
        });
    });
});
</script>
@endpush
