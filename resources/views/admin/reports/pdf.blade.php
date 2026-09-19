{{-- Rendered by dompdf, which supports only a narrow slice of CSS: no flex,
     no grid, no custom properties. Everything here is tables and inline rules
     on purpose. --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        @page { margin: 22mm 12mm 18mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #0E1B13; }

        .masthead { border-bottom: 2px solid #15703A; padding-bottom: 6px; margin-bottom: 10px; }
        .brand { font-size: 13pt; font-weight: bold; color: #0B3D20; }
        .brand span { color: #15703A; }
        .title { font-size: 11pt; font-weight: bold; margin-top: 4px; }
        .meta { font-size: 7.5pt; color: #5C6B61; margin-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #15703A; color: #fff; font-size: 7.5pt; text-align: left;
            padding: 5px 4px; border: 0.4pt solid #15703A;
        }
        table.data td { padding: 4px; border: 0.4pt solid #DCE8E0; font-size: 7.5pt; }
        table.data tr:nth-child(even) td { background: #F4F8F5; }
        td.num, th.num { text-align: right; }

        .note {
            margin-top: 10px; padding: 6px 8px; font-size: 7.5pt;
            background: #FEF6E7; border-left: 3px solid #B54708; color: #7A4A06;
        }
        .empty { padding: 24px; text-align: center; color: #5C6B61; font-size: 9pt; }

        /* dompdf resolves these counters at render time. */
        .foot { position: fixed; bottom: -12mm; left: 0; right: 0;
                font-size: 7pt; color: #5C6B61; border-top: 0.4pt solid #DCE8E0; padding-top: 4px; }
        .foot .page:after { content: counter(page) " / " counter(pages); }
    </style>
</head>
<body>
    <div class="masthead">
        <div class="brand">Tractor <span>Sarthi</span></div>
        <div class="title">{{ $report->title }}</div>
        <div class="meta">
            {{ $report->subtitle }} &nbsp;·&nbsp;
            {{ __('Generated :when', ['when' => $generatedAt->format('d M Y, H:i')]) }} &nbsp;·&nbsp;
            {{ trans_choice('{0} no rows|{1} 1 row|[2,*] :count rows', count($rows), ['count' => count($rows)]) }}
        </div>
    </div>

    @if (count($rows) === 0)
        <p class="empty">{{ __('Nothing matched this period.') }}</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach ($report->headings() as $heading)
                        <th class="{{ $report->isNumeric($heading) ? 'num' : '' }}">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($report->headings() as $i => $heading)
                            <td class="{{ $report->isNumeric($heading) ? 'num' : '' }}">
                                @php $value = $row[$i] ?? null; @endphp
                                {{ is_float($value) ? number_format($value, 0) : $value }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($truncated)
        <p class="note">
            {{ __('This PDF shows the first :n rows. A PDF longer than that is unreadable and slow to open — download the Excel version for the complete report.', ['n' => number_format(\App\Domain\Reporting\Services\ReportExporter::PDF_MAX_ROWS)]) }}
        </p>
    @endif

    <div class="foot">
        {{ __('Tractor Sarthi') }} &nbsp;·&nbsp; {{ __('Confidential — contains customer data') }}
        &nbsp;·&nbsp; <span class="page"></span>
    </div>
</body>
</html>
