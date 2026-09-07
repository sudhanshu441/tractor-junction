<?php

namespace App\Domain\Catalog\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Turns request input into a constrained product query.
 *
 * Every filter reads `product_filter_cache` (joined as `pfc`), never the EAV.
 * The same instance serves the full-page render and the AJAX partial, so a
 * shared URL and an in-page filter always produce identical results.
 */
class ProductFilter
{
    public const SORTS = [
        'popular' => ['products.popularity_score', 'desc'],
        'price-low' => ['products.price_min', 'asc'],
        'price-high' => ['products.price_min', 'desc'],
        'hp-low' => ['products.hp_min', 'asc'],
        'hp-high' => ['products.hp_min', 'desc'],
        'newest' => ['products.launch_year', 'desc'],
        'rating' => ['products.rating_avg', 'desc'],
    ];

    public function __construct(private readonly array $input) {}

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function apply(Builder $query): Builder
    {
        $query->join('product_filter_cache as pfc', 'pfc.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->select('products.*');

        $this->applyBrands($query);
        $this->applyCategory($query);
        $this->applyRange($query, 'pfc.hp', 'hp_min', 'hp_max');
        $this->applyRange($query, 'pfc.price', 'price_min', 'price_max');
        $this->applyEquals($query, 'pfc.wheel_drive', 'wheel_drive');
        $this->applyEquals($query, 'pfc.fuel_type', 'fuel_type');
        $this->applyEquals($query, 'pfc.cylinders', 'cylinders');
        $this->applyBool($query, 'pfc.power_steering', 'power_steering');
        $this->applyBool($query, 'pfc.ac_cabin', 'ac_cabin');

        if ($status = $this->value('status')) {
            $query->where('products.status', $status);
        }

        return $this->applySort($query);
    }

    public function applySort(Builder $query): Builder
    {
        [$column, $direction] = self::SORTS[$this->sort()] ?? self::SORTS['popular'];

        return $query->orderBy($column, $direction)->orderBy('products.id');
    }

    public function sort(): string
    {
        $sort = (string) $this->value('sort');

        return array_key_exists($sort, self::SORTS) ? $sort : 'popular';
    }

    /** Only the parameters that actually narrowed the query, for URLs and chips. */
    public function activeFilters(): array
    {
        $keys = ['brand', 'category', 'hp_min', 'hp_max', 'price_min', 'price_max',
            'wheel_drive', 'fuel_type', 'cylinders', 'power_steering', 'ac_cabin', 'status', 'sort'];

        return array_filter(
            array_intersect_key($this->input, array_flip($keys)),
            fn ($v) => $v !== null && $v !== '' && $v !== [],
        );
    }

    public function isEmpty(): bool
    {
        return $this->activeFilters() === [] || array_keys($this->activeFilters()) === ['sort'];
    }

    // ----- individual filters -----

    private function applyBrands(Builder $query): void
    {
        $brands = $this->arrayValue('brand');

        if ($brands) {
            $query->whereIn('pfc.brand_id', $brands);
        }
    }

    private function applyCategory(Builder $query): void
    {
        $categories = $this->arrayValue('category');

        if ($categories) {
            $query->whereIn('pfc.category_id', $categories);
        }
    }

    private function applyRange(Builder $query, string $column, string $minKey, string $maxKey): void
    {
        if (($min = $this->value($minKey)) !== null && $min !== '') {
            $query->where($column, '>=', (float) $min);
        }

        if (($max = $this->value($maxKey)) !== null && $max !== '') {
            $query->where($column, '<=', (float) $max);
        }
    }

    private function applyEquals(Builder $query, string $column, string $key): void
    {
        $values = $this->arrayValue($key);

        if ($values) {
            $query->whereIn($column, $values);
        }
    }

    private function applyBool(Builder $query, string $column, string $key): void
    {
        if ($this->value($key)) {
            $query->where($column, true);
        }
    }

    private function value(string $key): mixed
    {
        return $this->input[$key] ?? null;
    }

    private function arrayValue(string $key): array
    {
        $value = $this->value($key);

        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(is_array($value) ? $value : explode(',', (string) $value)));
    }
}
