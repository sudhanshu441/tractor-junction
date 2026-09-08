@extends('layouts.admin')

@section('title', __('Offers — Krishi Junction Admin'))
@section('page_title', __('Offers'))

@section('content')
<div class="row g-3 mb-3">
    @foreach ([__('Running now') => $counts['live'], __('Expired') => $counts['expired']] as $label => $value)
        <div class="col-6">
            <div class="kj-stat"><div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div></div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.offers.create') }}" class="btn btn-primary btn-sm">{{ __('Add offer') }}</a>
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>{{ __('Offer') }}</th><th>{{ __('Brand') }}</th><th>{{ __('Type') }}</th>
                    <th class="num">{{ __('Models') }}</th><th>{{ __('Runs') }}</th><th>{{ __('Status') }}</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($offers as $offer)
                @php $expired = $offer->ends_at && $offer->ends_at->isPast(); @endphp
                <tr>
                    <td>{{ $offer->title }}<div class="small text-muted-2 mono">/{{ $offer->slug }}</div></td>
                    <td class="small">{{ $offer->brand?->name ?? __('All brands') }}</td>
                    <td class="small text-muted-2">{{ str_replace('_', ' ', $offer->discount_type) }}</td>
                    <td class="num mono small">{{ $offer->products_count }}</td>
                    <td class="small text-muted-2">
                        {{ $offer->starts_at?->format('d M') ?? '—' }} → {{ $offer->ends_at?->format('d M Y') ?? __('open') }}
                    </td>
                    <td>
                        <span class="badge {{ $expired ? 'badge-muted' : ($offer->is_active ? 'badge-ok' : 'badge-muted') }}">
                            {{ $expired ? __('expired') : ($offer->is_active ? __('live') : __('hidden')) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted-2 py-4">{{ __('No offers yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $offers->links() }}</div>
@endsection
