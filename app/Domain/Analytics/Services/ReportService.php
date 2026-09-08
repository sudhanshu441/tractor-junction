<?php

namespace App\Domain\Analytics\Services;

use App\Models\Dealer;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\PageView;
use App\Models\SearchLog;
use App\Models\UsedListing;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the admin and dealer dashboards.
 *
 * Every method returns plain arrays shaped for Chart.js, and every series is
 * filled across the whole date range — a day with no leads has to appear as a
 * zero, otherwise the line chart silently closes the gap and the reader sees a
 * flat week where there was an outage.
 */
class ReportService
{
    /** @return array{labels: array<int, string>, keys: array<int, string>} */
    private function days(int $days): array
    {
        $labels = [];
        $keys = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $keys[] = $date->toDateString();
            $labels[] = $date->format('d M');
        }

        return ['labels' => $labels, 'keys' => $keys];
    }

    /** @param array<string, int|float> $rows keyed by date string */
    private function series(array $rows, array $keys): array
    {
        return array_map(fn (string $key) => (float) ($rows[$key] ?? 0), $keys);
    }

    /** Counts grouped by day, portable across MySQL and SQLite. */
    private function byDay(string $table, string $column, int $days, ?callable $constrain = null): array
    {
        $query = DB::table($table)
            ->selectRaw("date({$column}) as day, count(*) as total")
            ->where($column, '>=', today()->subDays($days - 1))
            ->groupBy('day');

        if ($constrain) {
            $constrain($query);
        }

        return $query->pluck('total', 'day')
            ->mapWithKeys(fn ($total, $day) => [substr((string) $day, 0, 10) => (int) $total])
            ->all();
    }

    /** The admin dashboard's headline chart: traffic, leads and listings. */
    public function overview(int $days = 30): array
    {
        ['labels' => $labels, 'keys' => $keys] = $this->days($days);

        return [
            'labels' => $labels,
            'datasets' => [
                'views' => $this->series($this->byDay('page_views', 'viewed_on', $days), $keys),
                'leads' => $this->series($this->byDay('leads', 'created_at', $days), $keys),
                'listings' => $this->series($this->byDay('used_listings', 'created_at', $days), $keys),
            ],
        ];
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        return [
            'views_today' => PageView::whereDate('viewed_on', today())->count(),
            'views_month' => PageView::where('viewed_on', '>=', today()->startOfMonth())->count(),
            'leads_today' => Lead::whereDate('created_at', today())->count(),
            'leads_month' => Lead::where('created_at', '>=', now()->startOfMonth())->count(),
            'listings_live' => UsedListing::where('status', 'live')->count(),
            'listings_pending' => UsedListing::where('status', 'pending')->count(),
            'dealers_verified' => Dealer::where('verification_status', 'verified')->count(),
            'dealers_pending' => Dealer::where('verification_status', 'pending')->count(),
            'loans_open' => LoanApplication::whereIn('status', ['submitted', 'under_review', 'docs_pending'])->count(),
        ];
    }

    /** Where the leads come from — the number that decides marketing spend. */
    public function leadsByType(int $days = 30): array
    {
        $rows = Lead::where('created_at', '>=', today()->subDays($days - 1))
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        return [
            'labels' => array_map(fn ($t) => ucfirst(str_replace('_', ' ', $t)), array_keys($rows)),
            'values' => array_map('intval', array_values($rows)),
        ];
    }

    public function leadsByState(int $days = 30, int $limit = 8): array
    {
        $rows = Lead::where('leads.created_at', '>=', today()->subDays($days - 1))
            ->join('states', 'states.id', '=', 'leads.state_id')
            ->selectRaw('states.name as state, count(*) as total')
            ->groupBy('states.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'state')
            ->all();

        return [
            'labels' => array_keys($rows),
            'values' => array_map('intval', array_values($rows)),
        ];
    }

    /**
     * What people searched for and did not find. This is a content brief, not a
     * vanity metric: every zero-result term is a page somebody wanted.
     *
     * @return array<int, array{term: string, searches: int}>
     */
    public function emptySearches(int $days = 30, int $limit = 15): array
    {
        return SearchLog::where('created_at', '>=', today()->subDays($days - 1))
            ->where('results_count', 0)
            ->selectRaw('term, count(*) as searches')
            ->groupBy('term')
            ->orderByDesc('searches')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['term' => $row->term, 'searches' => (int) $row->searches])
            ->all();
    }

    /** @return array<int, array{name: string, views: int}> */
    public function topPages(int $days = 30, int $limit = 10): array
    {
        return PageView::where('viewed_on', '>=', today()->subDays($days - 1))
            ->whereNotNull('viewable_type')
            ->selectRaw('viewable_type, viewable_id, count(*) as views')
            ->groupBy('viewable_type', 'viewable_id')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $model = rescue(fn () => app($row->viewable_type)::find($row->viewable_id), null, false);

                return [
                    'name' => $model?->title ?? $model?->name ?? $model?->display_name ?? class_basename($row->viewable_type).' #'.$row->viewable_id,
                    'views' => (int) $row->views,
                ];
            })
            ->all();
    }

    /** One dealer's own numbers, for the dealer dashboard. */
    public function forDealer(Dealer $dealer, int $days = 30): array
    {
        ['labels' => $labels, 'keys' => $keys] = $this->days($days);

        $assigned = $this->byDay('lead_assignments', 'assigned_at', $days,
            fn ($q) => $q->where('dealer_id', $dealer->id));

        $converted = DB::table('lead_assignments')
            ->join('leads', 'leads.id', '=', 'lead_assignments.lead_id')
            ->where('lead_assignments.dealer_id', $dealer->id)
            ->where('lead_assignments.assigned_at', '>=', today()->subDays($days - 1))
            ->where('leads.status', 'converted')
            ->selectRaw('date(lead_assignments.assigned_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($total, $day) => [substr((string) $day, 0, 10) => (int) $total])
            ->all();

        return [
            'labels' => $labels,
            'datasets' => [
                'leads' => $this->series($assigned, $keys),
                'converted' => $this->series($converted, $keys),
            ],
        ];
    }

    /** Conversion at each step of the lead funnel, most recent window. */
    public function funnel(int $days = 30): array
    {
        $base = Lead::where('created_at', '>=', today()->subDays($days - 1));

        $received = (clone $base)->count();
        $assigned = (clone $base)->whereNotIn('status', ['new', 'duplicate'])->count();
        $contacted = (clone $base)->whereIn('status', ['contacted', 'qualified', 'converted'])->count();
        $converted = (clone $base)->where('status', 'converted')->count();

        return [
            'labels' => [__('Received'), __('Assigned'), __('Contacted'), __('Converted')],
            'values' => [$received, $assigned, $contacted, $converted],
        ];
    }
}
