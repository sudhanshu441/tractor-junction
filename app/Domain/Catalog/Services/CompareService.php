<?php

namespace App\Domain\Catalog\Services;

use App\Models\Product;
use App\Models\SpecGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Comparison is session-first: a visitor builds a set without logging in, and
 * the resulting URL (mahindra-575-di-vs-swaraj-744-fe) is a real, indexable page.
 */
class CompareService
{
    public const MAX_ITEMS = 4;

    public const SESSION_KEY = 'kj.compare';

    public function ids(): array
    {
        return array_values(array_unique(session()->get(self::SESSION_KEY, [])));
    }

    public function add(int $productId): array
    {
        $ids = $this->ids();

        if (in_array($productId, $ids, true)) {
            return ['status' => 'ok', 'message' => __('Already in your comparison.'), 'ids' => $ids];
        }

        if (count($ids) >= self::MAX_ITEMS) {
            return [
                'status' => 'error',
                'message' => __('You can compare up to :max models. Remove one first.', ['max' => self::MAX_ITEMS]),
                'ids' => $ids,
            ];
        }

        $ids[] = $productId;
        session()->put(self::SESSION_KEY, $ids);

        return ['status' => 'ok', 'message' => __('Added to comparison.'), 'ids' => $ids];
    }

    public function remove(int $productId): array
    {
        $ids = array_values(array_diff($this->ids(), [$productId]));
        session()->put(self::SESSION_KEY, $ids);

        return ['status' => 'ok', 'message' => __('Removed from comparison.'), 'ids' => $ids];
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function products(?array $ids = null): Collection
    {
        $ids = $ids ?? $this->ids();

        if (! $ids) {
            return collect();
        }

        return Product::with(['brand', 'category', 'media', 'specValues.attribute.group', 'prices'])
            ->active()->whereIn('id', $ids)->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            ->values();
    }

    /** "mahindra-575-di-vs-swaraj-744-fe" */
    public function slugFor(Collection $products): string
    {
        return $products->map(fn (Product $p) => $p->brand->slug.'-'.$p->slug)->implode('-vs-');
    }

    public function productsFromSlug(string $slug): Collection
    {
        $parts = array_filter(explode('-vs-', $slug));

        $products = collect($parts)->map(function (string $part) {
            return Product::with(['brand', 'category', 'media', 'specValues.attribute.group', 'prices'])
                ->active()
                ->whereHas('brand', fn ($b) => $b->whereRaw('? LIKE CONCAT(slug, "%")', [$part]))
                ->get()
                ->first(fn (Product $p) => $p->brand->slug.'-'.$p->slug === $part);
        })->filter()->values();

        return $products;
    }

    /**
     * The comparison table: spec groups → rows, each row carrying one value per
     * product and a flag for whether the products actually differ on it.
     */
    public function matrix(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $groups = SpecGroup::with(['attributes' => fn ($q) => $q->where('is_comparable', true)])
            ->where('is_active', true)->orderBy('sort_order')->get();

        $matrix = [];

        foreach ($groups as $group) {
            $rows = [];

            foreach ($group->attributes as $attribute) {
                $values = $products->map(function (Product $product) use ($attribute) {
                    $value = $product->specValues->firstWhere('spec_attribute_id', $attribute->id);

                    return $this->format($value?->value, $attribute->unit);
                })->all();

                // A row where nobody has a value tells the reader nothing.
                if (count(array_filter($values, fn ($v) => $v !== '—')) === 0) {
                    continue;
                }

                $rows[] = [
                    'label' => $attribute->name,
                    'values' => $values,
                    'differs' => count(array_unique($values)) > 1,
                ];
            }

            if ($rows) {
                $matrix[] = ['group' => $group->name, 'rows' => $rows];
            }
        }

        return $matrix;
    }

    private function format(mixed $value, ?string $unit): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_numeric($value)) {
            $value = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }

        return trim($value.' '.($unit ?? ''));
    }

    public static function shortSlug(string $slug): string
    {
        return Str::limit($slug, 150, '');
    }
}
