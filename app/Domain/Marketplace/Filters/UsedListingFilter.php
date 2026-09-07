<?php

namespace App\Domain\Marketplace\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filters the public used-listing grid. Mirrors ProductFilter's contract so the
 * same AJAX pattern serves both marketplaces.
 */
class UsedListingFilter
{
    public const SORTS = [
        'newest' => ['used_listings.published_at', 'desc'],
        'price-low' => ['used_listings.expected_price', 'asc'],
        'price-high' => ['used_listings.expected_price', 'desc'],
        'year-new' => ['used_listings.manufacturing_year', 'desc'],
        'hours-low' => ['used_listings.engine_hours', 'asc'],
    ];

    public function __construct(private readonly array $input) {}

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function apply(Builder $query): Builder
    {
        $query->live();

        $this->applyIn($query, 'used_listings.brand_id', 'brand');
        $this->applyIn($query, 'used_listings.category_id', 'category');
        $this->applyIn($query, 'used_listings.state_id', 'state');
        $this->applyIn($query, 'used_listings.district_id', 'district');
        $this->applyIn($query, 'used_listings.condition', 'condition');
        $this->applyIn($query, 'used_listings.seller_type', 'seller_type');

        $this->applyRange($query, 'used_listings.expected_price', 'price_min', 'price_max');
        $this->applyRange($query, 'used_listings.manufacturing_year', 'year_min', 'year_max');
        $this->applyRange($query, 'used_listings.hp', 'hp_min', 'hp_max');

        if (filled($this->value('hours_max'))) {
            $query->where('used_listings.engine_hours', '<=', (int) $this->value('hours_max'));
        }

        if ($this->value('verified')) {
            $query->where('used_listings.is_verified', true);
        }

        return $this->applySort($query);
    }

    public function applySort(Builder $query): Builder
    {
        [$column, $direction] = self::SORTS[$this->sort()] ?? self::SORTS['newest'];

        // Featured listings surface first regardless of the chosen sort.
        return $query->orderByDesc('used_listings.is_featured')
            ->orderBy($column, $direction)
            ->orderByDesc('used_listings.id');
    }

    public function sort(): string
    {
        $sort = (string) $this->value('sort');

        return array_key_exists($sort, self::SORTS) ? $sort : 'newest';
    }

    public function activeFilters(): array
    {
        $keys = ['brand', 'category', 'state', 'district', 'condition', 'seller_type',
            'price_min', 'price_max', 'year_min', 'year_max', 'hp_min', 'hp_max',
            'hours_max', 'verified', 'sort'];

        return array_filter(
            array_intersect_key($this->input, array_flip($keys)),
            fn ($v) => $v !== null && $v !== '' && $v !== [],
        );
    }

    private function applyIn(Builder $query, string $column, string $key): void
    {
        if ($values = $this->arrayValue($key)) {
            $query->whereIn($column, $values);
        }
    }

    private function applyRange(Builder $query, string $column, string $minKey, string $maxKey): void
    {
        if (filled($this->value($minKey))) {
            $query->where($column, '>=', $this->value($minKey));
        }

        if (filled($this->value($maxKey))) {
            $query->where($column, '<=', $this->value($maxKey));
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
