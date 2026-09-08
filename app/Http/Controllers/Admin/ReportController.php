<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\UsedListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

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
        ]);
    }

    /**
     * Streamed rather than built in memory: a year of leads is a large export
     * and a report should never be the thing that exhausts a worker.
     */
    public function export(Request $request, string $dataset): StreamedResponse
    {
        abort_unless(in_array($dataset, ['leads', 'listings'], true), 404);

        $from = $request->query('from') ? today()->parse($request->query('from')) : today()->subDays(30);
        $to = $request->query('to') ? today()->parse($request->query('to')) : today();
        $showContact = $request->user()->can('leads.view_contact');

        $filename = 'krishi-junction-'.$dataset.'-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return Response::streamDownload(function () use ($dataset, $from, $to, $showContact) {
            $out = fopen('php://output', 'w');

            if ($dataset === 'leads') {
                fputcsv($out, ['Reference', 'Type', 'Name', 'Mobile', 'District', 'Status', 'Created']);

                Lead::with('district')
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('id')
                    ->chunk(500, function ($batch) use ($out, $showContact) {
                        foreach ($batch as $lead) {
                            fputcsv($out, [
                                $lead->reference_no,
                                $lead->type,
                                $lead->name,
                                // Contact numbers leave the system only for roles
                                // that are allowed to see them on screen.
                                $showContact ? $lead->mobile : $lead->masked_mobile,
                                $lead->district?->name,
                                $lead->status,
                                $lead->created_at?->toDateTimeString(),
                            ]);
                        }
                    });
            } else {
                fputcsv($out, ['Reference', 'Title', 'Brand', 'City', 'Price', 'Status', 'Views', 'Leads', 'Created']);

                UsedListing::with('brand', 'city')
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('id')
                    ->chunk(500, function ($batch) use ($out) {
                        foreach ($batch as $listing) {
                            fputcsv($out, [
                                $listing->reference_no,
                                $listing->title,
                                $listing->brand?->name,
                                $listing->city?->name,
                                $listing->expected_price,
                                $listing->status,
                                $listing->view_count,
                                $listing->lead_count,
                                $listing->created_at?->toDateTimeString(),
                            ]);
                        }
                    });
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
