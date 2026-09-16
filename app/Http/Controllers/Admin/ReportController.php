<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\Services\ReportService;
use App\Domain\Reporting\Services\ReportCatalogue;
use App\Domain\Reporting\Services\ReportExporter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportExporter $exporter,
    ) {}

    public function index(Request $request): View
    {
        $days = min(365, max(7, (int) $request->query('days', 30)));

        return view('admin.reports.index', [
            'days' => $days,
            'totals' => $this->reports->totals(),
            'overview' => $this->reports->overview($days),
            'leadsByType' => $this->reports->leadsByType($days),
            'leadsByState' => $this->reports->leadsByState($days),
            'funnel' => $this->reports->funnel($days),
            'topPages' => $this->reports->topPages($days),
            'emptySearches' => $this->reports->emptySearches($days),
            'downloads' => ReportCatalogue::index(),
        ]);
    }

    /**
     * Every report, in CSV, Excel or PDF.
     *
     * One definition drives all three formats, so a column can never appear in
     * the spreadsheet and go missing from the PDF. The permission is checked
     * here rather than only on the route, because an export takes data out of
     * the building.
     */
    public function download(Request $request, string $report, string $format, ReportCatalogue $catalogue)
    {
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $available = ReportCatalogue::index();
        abort_unless(isset($available[$report]), 404);
        abort_unless($request->user()->can($available[$report]['permission']), 403);

        $definition = $catalogue->make($report);

        activity()->causedBy($request->user())
            ->withProperties(['report' => $report, 'format' => $format])
            ->log('Downloaded a report');

        return $this->exporter->download($definition, $format);
    }
}
