<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\Services\VisitorJourney;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Anonymous visitors, and what they were looking for.
 *
 * This answers "who is on the site and what do they want" without pretending to
 * answer "what is their name" — a browser cannot be made to give that up. The
 * value here is the shape of demand and, once somebody does leave a number, the
 * history that arrives with it.
 */
class VisitorController extends Controller
{
    public function __construct(private readonly VisitorJourney $journey) {}

    public function index(Request $request): View
    {
        $days = min(90, max(1, (int) $request->query('days', 7)));
        $since = today()->subDays($days - 1);
        $intent = $request->query('intent');

        // Group in the database; loading every page view to group in PHP would
        // not survive real traffic.
        $visitors = PageView::selectRaw('visitor_id,
                count(*) as pages,
                max(created_at) as last_seen,
                min(created_at) as first_seen')
            ->whereNotNull('visitor_id')
            ->where('created_at', '>=', $since)
            ->when($intent, fn ($q) => $q->whereIn('visitor_id', fn ($sub) => $sub
                ->select('visitor_id')->from('page_views')
                ->where('intent', $intent)->where('created_at', '>=', $since)))
            ->groupBy('visitor_id')
            ->orderByDesc('last_seen')
            ->paginate(25)
            ->withQueryString();

        // Which of these browsers eventually left a number.
        $converted = Lead::whereIn('visitor_id', $visitors->pluck('visitor_id')->filter())
            ->get()
            ->keyBy('visitor_id');

        return view('admin.visitors.index', [
            'visitors' => $visitors,
            'converted' => $converted,
            'journeys' => $visitors->mapWithKeys(fn ($v) => [
                $v->visitor_id => $this->journey->summarise($v->visitor_id, 20),
            ]),
            'days' => $days,
            'intent' => $intent,
            'intents' => PageView::selectRaw('intent, count(distinct visitor_id) as visitors')
                ->whereNotNull('intent')
                ->where('created_at', '>=', $since)
                ->groupBy('intent')
                ->orderByDesc('visitors')
                ->pluck('visitors', 'intent')
                ->all(),
            'totals' => [
                'visitors' => PageView::whereNotNull('visitor_id')
                    ->where('created_at', '>=', $since)->distinct('visitor_id')->count('visitor_id'),
                'callbacks' => Lead::where('type', 'callback')->where('created_at', '>=', $since)->count(),
                'identified' => Lead::whereNotNull('visitor_id')->where('created_at', '>=', $since)->count(),
            ],
        ]);
    }
}
