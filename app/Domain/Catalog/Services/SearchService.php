<?php

namespace App\Domain\Catalog\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\SearchSynonym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * v1 search: MySQL FULLTEXT where available, LIKE elsewhere, with a synonym
 * table applied first so "महिंद्रा", "mhindra" and "mahindra" reach the same
 * products. Swappable for a search server behind this same interface.
 */
class SearchService
{
    public function products(string $term, int $limit = 20): Collection
    {
        $normalised = $this->normalise($term);

        if ($normalised === '') {
            return collect();
        }

        return $this->matching($normalised)
            ->with(['brand', 'category', 'media'])
            ->orderByDesc('popularity_score')
            ->limit($limit)
            ->get();
    }

    /**
     * People search the way they speak: "mahindra 575", not "575 DI XP Plus".
     * A product row only holds the model, so every word must match either the
     * model name or its brand — all words, so extra terms narrow rather than widen.
     */
    private function matching(string $normalised): Builder
    {
        $words = array_filter(explode(' ', $normalised));

        $query = Product::query()->active();

        foreach ($words as $word) {
            $query->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$word}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$word}%"))
                // "rotavator" is the category, not the model name — a farmer
                // searching for the machine should still find every one of them.
                ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$word}%")));
        }

        return $query;
    }

    /** Type-ahead: a few products and brands, grouped by type. */
    public function suggest(string $term, int $limit = 8): array
    {
        $normalised = $this->normalise($term);

        if (mb_strlen($normalised) < 2) {
            return ['products' => [], 'brands' => []];
        }

        $products = $this->matching($normalised)->with('brand')
            ->orderByDesc('popularity_score')->limit($limit)->get()
            ->map(fn (Product $p) => [
                'label' => $p->full_name,
                'url' => route('products.show', [$p->brand->slug, $p->slug]),
                'meta' => $p->hp_label,
            ])->all();

        $brands = Brand::active()
            ->where(function ($q) use ($normalised) {
                foreach (array_filter(explode(' ', $normalised)) as $word) {
                    $q->orWhere('name', 'like', "%{$word}%");
                }
            })
            ->limit(4)->get()
            ->map(fn (Brand $b) => [
                'label' => $b->name,
                'url' => route('brands.show', $b->slug),
                'meta' => __('Brand'),
            ])->all();

        return ['products' => $products, 'brands' => $brands];
    }

    /** Rewrites a query through the synonym table before it hits the database. */
    public function normalise(string $term): string
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term));

        if ($term === '') {
            return '';
        }

        // Cache a plain array: a serialised Eloquent/Support Collection can come
        // back as an incomplete class when the cache is read by another process.
        $synonyms = Cache::remember('catalog.search_synonyms', now()->addDay(),
            fn () => SearchSynonym::where('is_active', true)->pluck('canonical', 'term')->all());

        $words = collect(explode(' ', $term))
            ->map(fn (string $word) => $synonyms[mb_strtolower($word)] ?? $word);

        return $words->implode(' ');
    }

    public function log(string $term, int $results, ?int $userId, ?string $ip, string $context = 'global'): void
    {
        if (trim($term) === '') {
            return;
        }

        SearchLog::create([
            'term' => mb_substr($term, 0, 190),
            'context' => $context,
            'results_count' => $results,
            'user_id' => $userId,
            'ip' => $ip,
        ]);
    }

    /** @return Collection<int, array{term: string, hits: int}> */
    public function trending(int $limit = 8): Collection
    {
        $rows = Cache::remember('catalog.trending_searches', now()->addHour(),
            fn () => SearchLog::query()
                ->select('term', DB::raw('count(*) as hits'))
                ->where('created_at', '>=', now()->subDays(30))
                ->where('results_count', '>', 0)
                ->groupBy('term')->orderByDesc('hits')->limit($limit)
                ->get()
                ->map(fn ($row) => ['term' => (string) $row->term, 'hits' => (int) $row->hits])
                ->all());

        return collect($rows);
    }
}
