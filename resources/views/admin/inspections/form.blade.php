@extends('layouts.admin')

@section('title', __('Inspection :ref — Krishi Junction Admin', ['ref' => $inspection->reference_no]))
@section('page_title', __('Inspection report'))

@section('content')
@php
    $listing = $inspection->listing;
    $locked = $inspection->isApproved();
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h2 class="h6 mb-1">{{ $listing?->title ?: __('Listing removed') }}</h2>
        <p class="small text-muted-2 mb-0 mono">
            {{ $inspection->reference_no }} · {{ $listing?->reference_no }} ·
            {{ __('inspector: :name', ['name' => $inspection->inspector?->name ?? __('unassigned')]) }}
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge {{ $locked ? 'badge-ok' : 'badge-info' }}">{{ str_replace('_', ' ', $inspection->status) }}</span>
        <a href="{{ route('admin.inspections.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Back to list') }}</a>
    </div>
</div>

@if ($locked)
    <div class="alert alert-secondary small">
        {{ __('This report is approved and the listing carries the verified badge. Scores are locked.') }}
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <form id="inspection-form">
            @foreach ($checklist as $section => $items)
                <div class="card mb-3"><div class="card-body">
                    <h2 class="h6 mb-3">{{ ucfirst(str_replace('_', ' ', (string) $section)) }}</h2>

                    @foreach ($items as $item)
                        @php $existing = $scores->get($item->id); @endphp
                        <div class="row g-2 align-items-center py-2 border-bottom js-check-row" data-weight="{{ (float) $item->weight }}">
                            <div class="col-12 col-md-5">
                                <span class="small fw-semibold">{{ $item->name }}</span>
                                <div class="small text-muted-2">{{ __('weight :w', ['w' => rtrim(rtrim((string) $item->weight, '0'), '.')]) }}</div>
                            </div>
                            <div class="col-8 col-md-3">
                                <input type="range" class="form-range js-score" min="0" max="10" step="1"
                                       name="scores[{{ $item->id }}][score]"
                                       value="{{ $existing->score ?? 5 }}" {{ $locked ? 'disabled' : '' }}>
                            </div>
                            <div class="col-4 col-md-1 num mono js-score-value">{{ $existing->score ?? 5 }}/10</div>
                            <div class="col-12 col-md-3">
                                <input type="text" class="form-control form-control-sm"
                                       name="scores[{{ $item->id }}][remarks]" maxlength="300"
                                       placeholder="{{ __('Remarks') }}"
                                       value="{{ $existing->remarks ?? '' }}" {{ $locked ? 'disabled' : '' }}>
                            </div>
                        </div>
                    @endforeach
                </div></div>
            @endforeach

            @if ($checklist->isEmpty())
                <div class="alert alert-warning small">
                    {{ __('No checklist items are active. Add them before an inspector can score a machine.') }}
                </div>
            @endif

            <div class="card mb-3"><div class="card-body">
                <label class="form-label" for="summary">{{ __("Inspector's summary") }}</label>
                <textarea id="summary" name="summary" rows="4" maxlength="2000"
                          class="form-control form-control-sm"
                          placeholder="{{ __('What a buyer should know: leaks, tyre life, repairs due.') }}"
                          {{ $locked ? 'disabled' : '' }}>{{ $inspection->summary }}</textarea>
                <p class="small text-muted-2 mt-2 mb-0">{{ __('This text appears on the public listing once the report is approved.') }}</p>
            </div></div>

            @unless ($locked)
                <button type="submit" class="btn btn-primary" id="save-report">{{ __('Save report') }}</button>
            @endunless
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body text-center">
            <div class="kj-label mb-1">{{ __('Weighted score') }}</div>
            <div class="display-6 mono" id="live-score">
                {{ $inspection->overall_score !== null ? number_format((float) $inspection->overall_score, 1) : '—' }}
            </div>
            <div class="mt-1">
                <span class="badge badge-muted" id="live-grade">{{ $inspection->grade ?? '—' }}</span>
            </div>
            <p class="small text-muted-2 mt-2 mb-0">
                {{ __('A ≥ 85 · B ≥ 70 · C ≥ 50 · D below 50') }}
            </p>
        </div></div>

        @if ($inspection->valuation_min)
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Valuation band') }}</h2>
                <p class="mb-0 mono">₹{{ number_format((float) $inspection->valuation_min) }} – ₹{{ number_format((float) $inspection->valuation_max) }}</p>
                <p class="small text-muted-2 mb-0">{{ __('Derived from the inspected grade, not the seller’s own description.') }}</p>
            </div></div>
        @endif

        @can('inspections.approve')
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Approval') }}</h2>
                @if ($locked)
                    <p class="small text-muted-2 mb-0">{{ __('Approved — the listing shows the verified badge.') }}</p>
                @elseif ($inspection->status === 'completed')
                    <p class="small text-muted-2">{{ __('Approving publishes the grade and gives the listing its verified badge.') }}</p>
                    <button type="button" class="btn btn-primary w-100" id="approve-report">{{ __('Approve report') }}</button>
                @else
                    <p class="small text-muted-2 mb-0">{{ __('The report has to be saved before it can be approved.') }}</p>
                @endif
            </div></div>
        @endcan

        @if ($listing && $listing->images->isNotEmpty())
            <div class="card"><div class="card-body">
                <h2 class="h6 mb-2">{{ __('Seller photos') }}</h2>
                <div class="row g-2">
                    @foreach ($listing->images as $image)
                        <div class="col-4">
                            <a href="{{ $image->url() }}" target="_blank" rel="noopener">
                                <img src="{{ $image->thumbnailUrl() }}" alt="{{ $image->angle }}"
                                     class="img-fluid rounded" loading="lazy">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div></div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var form = $('#inspection-form');

    // The grade the server will compute, shown as the inspector moves the sliders.
    function recalc() {
        var weighted = 0, total = 0;

        form.find('.js-check-row').each(function () {
            var weight = parseFloat($(this).data('weight')) || 0;
            var score = parseInt($(this).find('.js-score').val(), 10) || 0;
            $(this).find('.js-score-value').text(score + '/10');
            weighted += score * weight;
            total += 10 * weight;
        });

        if (!total) { return; }

        var overall = Math.round(weighted / total * 1000) / 10;
        var grade = overall >= 85 ? 'A' : overall >= 70 ? 'B' : overall >= 50 ? 'C' : 'D';
        $('#live-score').text(overall.toFixed(1));
        $('#live-grade').text(grade);
    }

    form.on('input', '.js-score', recalc);
    recalc();

    form.on('submit', async function (event) {
        event.preventDefault();
        var button = $('#save-report').prop('disabled', true);

        var response = await KJ.request({
            url: '{{ route('admin.inspections.complete', $inspection) }}',
            method: 'POST',
            data: form.serialize(),
        });

        button.prop('disabled', false);

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            setTimeout(function () { window.location.reload(); }, 800);
        } else {
            KJ.toast(response.message || '{{ __('Could not save the report.') }}', 'danger');
        }
    });

    $('#approve-report').on('click', async function () {
        var button = $(this).prop('disabled', true);
        var response = await KJ.request({
            url: '{{ route('admin.inspections.approve', $inspection) }}',
            method: 'POST',
        });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            setTimeout(function () { window.location.reload(); }, 800);
        } else {
            button.prop('disabled', false);
            KJ.toast(response.message || '{{ __('Could not approve the report.') }}', 'danger');
        }
    });
});
</script>
@endpush
