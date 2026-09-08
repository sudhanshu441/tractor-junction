@extends('layouts.admin')

@section('title', $dealer->display_name.' — Krishi Junction Admin')
@section('page_title', $dealer->display_name)

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h6 mb-1">{{ $dealer->business_name }}</h2>
                    <p class="small text-muted-2 mb-0 mono">
                        {{ $dealer->code }} · {{ $canSeeContact ? $dealer->mobile : $dealer->masked_mobile }}
                        @if ($dealer->email) · {{ $dealer->email }} @endif
                    </p>
                </div>
                <span class="badge {{ $dealer->verification_status === 'verified' ? 'badge-ok' : 'badge-warn' }}">
                    {{ ucfirst($dealer->verification_status) }}
                </span>
            </div>

            <div class="row g-3">
                @foreach ([
                    __('Type') => ucfirst(str_replace('_', ' ', $dealer->dealer_type)),
                    __('GSTIN') => $dealer->gstin ?: '—',
                    __('Location') => trim(($dealer->city?->name ?? '').', '.($dealer->state?->name ?? ''), ', '),
                    __('Contact person') => $dealer->contact_person ?: '—',
                    __('Registered') => $dealer->created_at->format('d M Y'),
                    __('Response score') => number_format((float) $dealer->response_score, 1).' / 10',
                ] as $label => $value)
                    <div class="col-6 col-md-4">
                        <div class="kj-stat"><div class="k">{{ $label }}</div>
                        <div class="v" style="font-size:.95rem;">{{ $value }}</div></div>
                    </div>
                @endforeach
            </div>

            @if ($dealer->brands->isNotEmpty())
                <div class="mt-3">
                    <div class="kj-label mb-2">{{ __('Brands claimed') }}</div>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach ($dealer->brands as $brand)
                            <span class="badge badge-muted">{{ $brand->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($dealer->address)
                <div class="mt-3">
                    <div class="kj-label mb-1">{{ __('Address') }}</div>
                    <p class="small mb-0">{{ $dealer->address }} {{ $dealer->pincode }}</p>
                </div>
            @endif
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Documents') }}</h2>
            <p class="small text-muted-2">
                {{ __('These open through a signed link that expires in five minutes, and every open is logged.') }}
            </p>

            @forelse ($dealer->documents as $document)
                <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                    <div>
                        <b class="small">{{ ucfirst(str_replace('_', ' ', $document->doc_type)) }}</b>
                        <div class="small text-muted-2">{{ $document->original_name }}</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge {{ $document->status === 'approved' ? 'badge-ok' : 'badge-warn' }}">
                            {{ $document->status }}
                        </span>
                        <a href="{{ $documentLinks[$document->id] ?? '#' }}" class="btn btn-sm btn-outline-primary">
                            {{ __('Open') }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning small mb-0">
                    {{ __('No documents uploaded yet. A dealer normally cannot be verified without GST and PAN.') }}
                </div>
            @endforelse
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Verification') }}</h2>

            @if (count($transitions))
                <label class="form-label" for="verify-status">{{ __('Decision') }}</label>
                <select id="verify-status" class="form-select form-select-sm mb-2">
                    @foreach ($transitions as $t)
                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="verify-remarks">{{ __('Reason — the dealer sees this') }}</label>
                <textarea id="verify-remarks" class="form-control form-control-sm mb-2" rows="2"></textarea>

                <button class="btn btn-primary btn-sm w-100" id="save-verification">{{ __('Save decision') }}</button>
            @else
                <p class="small text-muted-2 mb-0">{{ __('No further transitions from this state.') }}</p>
            @endif
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Plan and volume') }}</h2>
            @foreach ([
                __('Plan') => $usage['plan'] ?? '—',
                __('Leads this month') => $usage['used'].($usage['limit'] ? ' / '.$usage['limit'] : ''),
                __('Leads today') => $usage['daily_used'].($usage['daily_cap'] ? ' / '.$usage['daily_cap'] : ''),
                __('Inventory') => $dealer->inventory->count(),
                __('Branches') => $dealer->branches->count(),
            ] as $label => $value)
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted-2">{{ $label }}</span><span class="mono">{{ $value }}</span>
                </div>
            @endforeach
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#save-verification').on('click', function () {
        var status = $('#verify-status').val();
        var remarks = $('#verify-remarks').val();

        if (status !== 'verified' && !remarks) {
            $('#verify-remarks').addClass('is-invalid').trigger('focus');
            return KJ.toast('A reason is required — the dealer sees it.', 'warning');
        }

        KJ.request({
            url: '{{ route('admin.dealers.verify', $dealer) }}', method: 'POST',
            data: { status: status, remarks: remarks },
        }).then(function (res) {
            KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') { window.location.reload(); }
        });
    });
});
</script>
@endpush
