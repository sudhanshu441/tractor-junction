<?php

namespace App\Domain\Reporting\Services;

use App\Support\Csv;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders a ReportDefinition as CSV, Excel or PDF.
 *
 * CSV and Excel stream and chunk, so a report of any size costs roughly constant
 * memory. PDF cannot stream — dompdf has to lay the whole document out — so it
 * is deliberately capped and tells the reader when it has been truncated rather
 * than silently printing a partial report.
 */
class ReportExporter
{
    /** Rows read per database round trip. */
    public const CHUNK = 500;

    /** A PDF beyond this is unreadable anyway, and dompdf runs out of memory. */
    public const PDF_MAX_ROWS = 2000;

    public function download(ReportDefinition $report, string $format): StreamedResponse|\Illuminate\Http\Response
    {
        return match ($format) {
            'xlsx' => $this->excel($report),
            'pdf' => $this->pdf($report),
            default => $this->csv($report),
        };
    }

    public function csv(ReportDefinition $report): StreamedResponse
    {
        return Response::streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            // Excel opens a UTF-8 CSV as mojibake without a byte order mark,
            // which matters here: names and places are often Devanagari.
            fwrite($out, "\xEF\xBB\xBF");
            Csv::put($out, $report->headings());

            ($report->query)()->chunk(self::CHUNK, function ($rows) use ($out, $report) {
                foreach ($rows as $model) {
                    Csv::put($out, $report->row($model));
                }
            });

            fclose($out);
        }, $report->filename('csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function excel(ReportDefinition $report): StreamedResponse
    {
        return Response::streamDownload(function () use ($report) {
            $options = new Options;
            $options->setColumnWidth(18, ...range(1, count($report->headings())));

            $writer = new Writer($options);
            $writer->openToFile('php://output');

            $header = (new Style)
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('15703A');   // brand green, matching the site

            $writer->addRow(Row::fromValues($report->headings(), $header));

            ($report->query)()->chunk(self::CHUNK, function ($rows) use ($writer, $report) {
                foreach ($rows as $model) {
                    // Cast to string: Excel otherwise reads a mobile number as a
                    // float and renders 9.8123e+9.
                    $writer->addRow(Row::fromValues(array_map(
                        fn ($value) => $value === null ? '' : (string) $value,
                        $report->row($model),
                    )));
                }
            });

            $writer->close();
        }, $report->filename('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(ReportDefinition $report): \Illuminate\Http\Response
    {
        $rows = [];
        $truncated = false;

        ($report->query)()->chunk(self::CHUNK, function ($batch) use (&$rows, &$truncated, $report) {
            foreach ($batch as $model) {
                if (count($rows) >= self::PDF_MAX_ROWS) {
                    $truncated = true;

                    return false;   // stop chunking
                }

                $rows[] = $report->row($model);
            }
        });

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'report' => $report,
            'rows' => $rows,
            'truncated' => $truncated,
            'generatedAt' => now(),
        ])->setPaper('a4', $report->orientation);

        return $pdf->download($report->filename('pdf'));
    }
}
