<?php

namespace App\Domain\Catalog\Services;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Facet counts for a listing page.
 *
 * Counts are computed against product_filter_cache and cached for 15 minutes,
 * keyed by the current filter set plus a version counter that any catalogue
 * write increments — so a price or spec edit invalidates every facet at once.
 */
class FacetService
{
    public function __construct(private readonly FilterCacheService $filterCache) {}

    public function for(?Category $category, array $filters = [], ?string $type = null): array
    {
        $key = 'catalog.facets.v'.FilterCacheService::facetVersion()
            .'.'.($category?->id ?? 'all').'.'.($type ?? 'any').'.'.md5(json_encode($filters));

        return Cache::remember($key, now()->addMinutes(15), function () use ($category, $filters, $type) {
            // Each facet is counted with every OTHER active filter applied, but not
            // its own — so the numbers show what picking that option would give you,
            // and a user can never choose an option that returns nothing.
            $base = fn (?string $except = null) => DB::table('product_filter_cache as pfc')
                ->join('products', 'products.id', '=', 'pfc.product_id')
                ->whereNull('products.deleted_at')
                ->where('products.is_active', true)
                ->when($category, fn ($q) => $q->where('pfc.category_id', $category->id))
                ->when($type, fn ($q) => $q->join('categories', 'categories.id', '=', 'pfc.category_id')
                    ->where('categories.type', $type))
                ->tap(fn ($q) => $this->applyFilters($q, $filters, $except));

            return [
                'brands' => $this->brandCounts($base('brand'), $filters),
                'hp_bands' => $this->bandCounts($base('hp'), 'pfc.hp', $this->hpBands()),
                'price_bands' => $this->bandCounts($base('price'), 'pfc.price', $this->priceBands()),
                'wheel_drive' => $this->columnCounts($base('wheel_drive'), 'pfc.wheel_drive'),
                'fuel_type' => $this->columnCounts($base('fuel_type'), 'pfc.fuel_type'),
                'cylinders' => $this->columnCounts($base('cylinders'), 'pfc.cylinders'),
                'features' => [
                    'power_steering' => (clone $base('power_steering'))->where('pfc.power_steering', true)->count(),
                    'ac_cabin' => (clone $base('ac_cabin'))->where('pfc.ac_cabin', true)->count(),
                ],
                'total' => $base()->count(),
            ];
        });
    }

    /**
     * Mirrors ProductFilter against the query builder used for counting.
     *
     * @param  string|null  $except  facet key to leave unconstrained
     */
    private function applyFilters($query, array $filters, ?string $except = null): void
    {
        $arrayOf = function (string $key) use ($filters): array {
            $value = $filters[$key] ?? null;

            if ($value === null || $value === '') {
                return [];
            }

            return array_values(array_filter(is_array($value) ? $value : explode(',', (string) $value)));
        };

        if ($except !== 'brand' && $brands = $arrayOf('brand')) {
            $query->whereIn('pfc.brand_id', $brands);
        }

        if ($except !== 'category' && $categories = $arrayOf('category')) {
            $query->whereIn('pfc.category_id', $categories);
        }

        if ($except !== 'hp') {
            if (filled($filters['hp_min'] ?? null)) {
                $query->where('pfc.hp', '>=', (float) $filters['hp_min']);
            }
            if (filled($filters['hp_max'] ?? null)) {
                $query->where('pfc.hp', '<=', (float) $filters['hp_max']);
            }
        }

        if ($except !== 'price') {
            if (filled($filters['price_min'] ?? null)) {
                $query->where('pfc.price', '>=', (float) $filters['price_min']);
            }
            if (filled($filters['price_max'] ?? null)) {
                $query->where('pfc.price', '<=', (float) $filters['price_max']);
            }
        }

        foreach (['wheel_drive' => 'pfc.wheel_drive', 'fuel_type' => 'pfc.fuel_type', 'cylinders' => 'pfc.cylinders'] as $key => $column) {
            if ($except !== $key && $values = $arrayOf($key)) {
                $query->whereIn($column, $values);
            }
        }

        foreach (['power_steering', 'ac_cabin'] as $flag) {
            if ($except !== $flag && ! empty($filters[$flag])) {
                $query->where('pfc.'.$flag, true);
            }
        }
    }

    public function hpBands(): array
    {
        return [
            ['label' => 'Under 25 HP', 'slug' => 'under-25-hp', 'min' => 0, 'max' => 24.99],
            ['label' => '25-35 HP', 'slug' => '25-35-hp', 'min' => 25, 'max' => 35],
            ['label' => '35-40 HP', 'slug' => '35-40-hp', 'min' => 35, 'max' => 40],
            ['label' => '40-50 HP', 'slug' => '40-50-hp', 'min' => 40, 'max' => 50],
            ['label' => '50-60 HP', 'slug' => '50-60-hp', 'min' => 50, 'max' => 60],
            ['label' => 'Above 60 HP', 'slug' => 'above-60-hp', 'min' => 60, 'max' => 999],
        ];
    }

    public function priceBands(): array
    {
        return [
            ['label' => 'Under ₹3 Lakh', 'slug' => 'under-3-lakh', 'min' => 0, 'max' => 300000],
            ['label' => '₹3-5 Lakh', 'slug' => '3-5-lakh', 'min' => 300000, 'max' => 500000],
            ['label' => '₹5-7 Lakh', 'slug' => '5-7-lakh', 'min' => 500000, 'max' => 700000],
            ['label' => '₹7-10 Lakh', 'slug' => '7-10-lakh', 'min' => 700000, 'max' => 1000000],
            ['label' => 'Above ₹10 Lakh', 'slug' => 'above-10-lakh', 'min' => 1000000, 'max' => 99999999],
        ];
    }

    public function bandBySlug(string $type, string $slug): ?array
    {
        $bands = $type === 'hp' ? $this->hpBands() : $this->priceBands();

        return collect($bands)->firstWhere('slug', $slug);
    }

    private function brandCounts($query, array $filters): array
    {
        $rows = $query->select('pfc.brand_id', DB::raw('count(*) as total'))
            ->groupBy('pfc.brand_id')->pluck('total', 'brand_id');

        return Brand::active()->whereIn('id', $rows->keys())->orderBy('name')->get()
            ->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'count' => (int) ($rows[$b->id] ?? 0),
                'checked' => in_array((string) $b->id, (array) ($filters['brand'] ?? []), true),
            ])
            ->sortByDesc('count')->values()->all();
    }

    private function bandCounts($query, string $column, array $bands): array
    {
        return collect($bands)->map(function (array $band) use ($query, $column) {
            $band['count'] = (clone $query)
                ->whereBetween($column, [$band['min'], $band['max']])
                ->count();

            return $band;
        })->all();
    }

    private function columnCounts($query, string $column): array
    {
        return $query->select($column.' as value', DB::raw('count(*) as total'))
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->map(fn ($row) => ['value' => $row->value, 'count' => (int) $row->total])
            ->all();
    }
}
