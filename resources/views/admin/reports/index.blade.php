@extends('layouts.admin')

@section('title', __('Reports — Tractor Sarthi Admin'))
@section('page_title', __('Reports'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2">
        @foreach ([7 => __('7 days'), 30 => __('30 days'), 90 => __('90 days'), 365 => __('1 year')] as $value => $label)
            <a href="{{ route('admin.reports.index', ['days' => $value]) }}"
               class="btn btn-sm {{ $days === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>
    <a href="#downloads" class="btn btn-primary btn-sm">{{ __('Download a report') }}</a>
</div>

<div class="row g-3 mb-3">
    @foreach ([
        __('Views today') => $totals['views_today'],
        __('Views this month') => $totals['views_month'],
        __('Leads today') => $totals['leads_today'],
        __('Leads this month') => $totals['leads_month'],
        __('Live listings') => $totals['listings_live'],
        __('Verified dealers') => $totals['dealers_verified'],
    ] as $label => $value)
        <div class="col-6 col-lg-2">
            <div class="kj-stat"><div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div></div>
        </div>
    @endforeach
</div>

<div class="card mb-3"><div class="card-body">
    <h2 class="h6 mb-3">{{ __('Traffic, leads and listings') }}</h2>
    <div style="height: 320px;"><canvas id="chart-overview" aria-label="{{ __('Daily views, leads and listings') }}" role="img"></canvas></div>
</div></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('Lead funnel') }}</h2>
            <div style="height: 260px;"><canvas id="chart-funnel" role="img" aria-label="{{ __('Lead funnel') }}"></canvas></div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('Leads by type') }}</h2>
            @if (count($leadsByType['values']))
                <div style="height: 260px;"><canvas id="chart-types" role="img" aria-label="{{ __('Leads by type') }}"></canvas></div>
            @else
                <p class="small text-muted-2">{{ __('No leads in this period.') }}</p>
            @endif
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('Leads by state') }}</h2>
            @if (count($leadsByState['values']))
                <div style="height: 260px;"><canvas id="chart-states" role="img" aria-label="{{ __('Leads by state') }}"></canvas></div>
            @else
                <p class="small text-muted-2">{{ __('No leads carry a state in this period.') }}</p>
            @endif
        </div></div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 mb-1">{{ __('Most-viewed pages') }}</h2>
            <p class="small text-muted-2">{{ __('Where attention actually goes.') }}</p>
            <table class="table table-sm align-middle mb-0">
                <tbody>
                @forelse ($topPages as $page)
                    <tr>
                        <td class="small">{{ $page['name'] }}</td>
                        <td class="num mono small">{{ number_format($page['views']) }}</td>
                    </tr>
                @empty
                    <tr><td class="small text-muted-2">{{ __('No page views recorded yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 mb-1">{{ __('Searches that found nothing') }}</h2>
            <p class="small text-muted-2">
                {{ __('Each of these is a page somebody wanted and we do not have. This is a content brief, not a vanity metric.') }}
            </p>
            <table class="table table-sm align-middle mb-0">
                <tbody>
                @forelse ($emptySearches as $row)
                    <tr>
                        <td class="small">{{ $row['term'] }}</td>
                        <td class="num mono small">{{ $row['searches'] }}</td>
                    </tr>
                @empty
                    <tr><td class="small text-muted-2">{{ __('Every search found something. Good.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
    </div>
</div>
<section class="card mt-4" id="downloads"><div class="card-body">
    <h2 class="h6 mb-1">{{ __('Download a report') }}</h2>
    <p class="small text-muted-2">
        {{ __('Excel and CSV contain every matching row. A PDF is capped at :n rows because a longer one is unreadable — it says so on the last page when it has been cut.', ['n' => number_format(\App\Domain\Reporting\Services\ReportExporter::PDF_MAX_ROWS)]) }}
    </p>

    <form method="GET" class="row g-2 align-items-end mb-3" id="report-range">
        <div class="col-auto">
            <label class="form-label" for="from">{{ __('From') }}</label>
            <input type="date" id="from" name="from" class="form-control form-control-sm"
                   value="{{ request('from', today()->subDays($days)->toDateString()) }}">
        </div>
        <div class="col-auto">
            <label class="form-label" for="to">{{ __('To') }}</label>
            <input type="date" id="to" name="to" class="form-control form-control-sm"
                   value="{{ request('to', today()->toDateString()) }}">
        </div>
        <div class="col-auto">
            <span class="small text-muted-2">{{ __('Applies to reports that have a date. The catalogue and dealer network are always complete.') }}</span>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <tbody>
            @foreach ($downloads as $key => $meta)
                @continue(! auth()->user()->can($meta['permission']))
                <tr>
                    <td>
                        <b class="small">{{ __($meta['title']) }}</b>
                        <div class="small text-muted-2">{{ __($meta['description']) }}</div>
                    </td>
                    <td class="text-end text-nowrap">
                        @foreach (['xlsx' => __('Excel'), 'csv' => __('CSV'), 'pdf' => __('PDF')] as $format => $label)
                            <a class="btn btn-sm btn-outline-primary js-download"
                               data-report="{{ $key }}" data-format="{{ $format }}"
                               href="{{ route('admin.reports.download', ['report' => $key, 'format' => $format]) }}">{{ $label }}</a>
                        @endforeach
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @unless (auth()->user()->can('leads.view_contact'))
        <p class="small text-muted-2 mt-3 mb-0">
            {{ __('Your role sees masked contact numbers, so the numbers in these downloads are masked too.') }}
        </p>
    @endunless
</div></section>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
$(function () {
    // Carry the chosen range onto every download link as it changes.
    function applyRange() {
        var from = $('#from').val(), to = $('#to').val();

        $('.js-download').each(function () {
            var url = new URL($(this).attr('href'), window.location.origin);
            if (from) { url.searchParams.set('from', from); }
            if (to) { url.searchParams.set('to', to); }
            $(this).attr('href', url.pathname + url.search);
        });
    }

    $('#report-range').on('submit', function (e) { e.preventDefault(); });
    $('#from, #to').on('change', applyRange);
    applyRange();

    var green = '#15703A', greenSoft = 'rgba(21,112,58,.12)', ink3 = '#6B7B70';
    var line = { borderWidth: 2, tension: .3, pointRadius: 0, pointHoverRadius: 4 };

    Chart.defaults.font.family = 'IBM Plex Sans, system-ui, sans-serif';
    Chart.defaults.color = ink3;

    new Chart(document.getElementById('chart-overview'), {
        type: 'line',
        data: {
            labels: @json($overview['labels']),
            datasets: [
                Object.assign({ label: '{{ __('Views') }}', data: @json($overview['datasets']['views']),
                    borderColor: green, backgroundColor: greenSoft, fill: true }, line),
                Object.assign({ label: '{{ __('Leads') }}', data: @json($overview['datasets']['leads']),
                    borderColor: '#175CD3' }, line),
                Object.assign({ label: '{{ __('Listings') }}', data: @json($overview['datasets']['listings']),
                    borderColor: '#B54708' }, line),
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } },
        },
    });

    new Chart(document.getElementById('chart-funnel'), {
        type: 'bar',
        data: {
            labels: @json($funnel['labels']),
            datasets: [{ data: @json($funnel['values']), backgroundColor: green, borderRadius: 4 }],
        },
        options: {
            indexAxis: 'y', maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } },
        },
    });

    // Each chart is drawn only when its canvas exists — an empty period renders
    // a sentence instead of an empty frame, which reads as broken.
    if (document.getElementById('chart-types')) new Chart(document.getElementById('chart-types'), {
        type: 'doughnut',
        data: {
            labels: @json($leadsByType['labels']),
            datasets: [{
                data: @json($leadsByType['values']),
                backgroundColor: ['#15703A', '#2E8B57', '#5BA97B', '#8FC7A6', '#B54708', '#175CD3', '#B42318', '#6B7B70', '#0B3D20'],
            }],
        },
        options: {
            maintainAspectRatio: false, cutout: '58%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
        },
    });

    if (document.getElementById('chart-states')) new Chart(document.getElementById('chart-states'), {
        type: 'bar',
        data: {
            labels: @json($leadsByState['labels']),
            datasets: [{ data: @json($leadsByState['values']), backgroundColor: green, borderRadius: 4 }],
        },
        options: {
            indexAxis: 'y', maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } },
        },
    });
});
</script>
@endpush
