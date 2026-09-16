<?php

namespace App\Domain\Analytics\Services;

use App\Models\PageView;
use Illuminate\Http\Request;

/**
 * What an anonymous visitor looked at, summarised for whoever calls them back.
 *
 * A raw list of forty URLs tells a salesperson nothing. What they need before
 * dialling is the shape of the interest: buying or selling, which brands, which
 * HP range, which budget — so the first sentence of the call is about the right
 * machine.
 */
class VisitorJourney
{
    /** URL patterns that say what somebody is here to do. */
    public const INTENTS = [
        'sell' => ['sell'],
        'finance' => ['loan', 'emi-calculator'],
        'insurance' => ['tractor-insurance'],
        'dealer' => ['dealers'],
        'buy' => ['tractors', 'used', 'implements', 'harvesters', 'compare', 'farm-tools', 'tractor-tyres'],
    ];

    /** The pages worth prompting on — high intent, and the visitor is deep enough in to be serious. */
    public const PROMPT_ON = ['sell', 'buy', 'finance'];

    public function intentFor(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        // A Hindi URL carries the same intent as its English twin.
        if (($segments[0] ?? null) === 'hi') {
            array_shift($segments);
        }

        $first = $segments[0] ?? null;

        if ($first === null) {
            return null;
        }

        foreach (self::INTENTS as $intent => $prefixes) {
            if (in_array($first, $prefixes, true)) {
                return $intent;
            }
        }

        return null;
    }

    /**
     * A plain-array summary of one visitor, safe to store on a lead.
     *
     * @return array<string, mixed>
     */
    public function summarise(string $visitorId, int $limit = 40): array
    {
        $views = PageView::where('visitor_id', $visitorId)
            ->with('viewable')
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($views->isEmpty()) {
            return [];
        }

        $intents = $views->pluck('intent')->filter()->countBy()->sortDesc();

        $machines = $views
            ->map(fn (PageView $v) => $v->viewable)
            ->filter()
            ->map(fn ($m) => $m->full_name ?? $m->title ?? $m->name ?? $m->display_name)
            ->filter()
            ->unique()
            ->take(8)
            ->values();

        $brands = $views
            ->map(fn (PageView $v) => $v->viewable?->brand?->name ?? $v->viewable?->relationLoaded('brand'))
            ->filter(fn ($b) => is_string($b))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(4)
            ->values();

        $first = $views->last();

        return [
            'visitor_id' => $visitorId,
            'pages_viewed' => $views->count(),
            'primary_intent' => $intents->keys()->first(),
            'intents' => $intents->all(),
            'machines_viewed' => $machines->all(),
            'brands_viewed' => $brands->all(),
            'first_seen' => $first?->created_at?->toIso8601String(),
            'last_seen' => $views->first()?->created_at?->toIso8601String(),
            'landed_on' => $first?->url,
            'came_from' => $first?->referer,
            'utm_source' => $views->pluck('utm_source')->filter()->first(),
            'recent_pages' => $views->take(10)->pluck('url')->filter()->values()->all(),
        ];
    }

    /** One line for a lead list: "Buying · Mahindra, Swaraj · 12 pages". */
    public function headline(?array $summary): ?string
    {
        if (blank($summary)) {
            return null;
        }

        $parts = [];

        if ($summary['primary_intent'] ?? null) {
            $parts[] = ucfirst($summary['primary_intent']);
        }

        if ($summary['brands_viewed'] ?? []) {
            $parts[] = implode(', ', array_slice($summary['brands_viewed'], 0, 3));
        }

        if ($summary['pages_viewed'] ?? 0) {
            $parts[] = trans_choice('{1} 1 page|[2,*] :count pages', $summary['pages_viewed'], [
                'count' => $summary['pages_viewed'],
            ]);
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public static function idFrom(Request $request): ?string
    {
        $id = $request->attributes->get('visitor_id');

        return is_string($id) && $id !== '' ? $id : null;
    }
}
