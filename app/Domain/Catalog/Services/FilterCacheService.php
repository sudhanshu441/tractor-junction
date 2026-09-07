<?php

namespace App\Domain\Catalog\Services;

use App\Models\Product;
use App\Models\ProductFilterCache;
use App\Models\SpecAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps `product_filter_cache` in step with the EAV.
 *
 * Facet queries never touch product_spec_values — they hit this one flat, indexed
 * row per product. The EAV stays the source of truth for display; this is a
 * derived read model, rebuilt whenever a product or its specs change.
 */
class FilterCacheService
{
    /** spec attribute slug => filter_cache column */
    public const MAP = [
        'engine-hp' => 'hp',
        'wheel-drive' => 'wheel_drive',
        'no-of-cylinders' => 'cylinders',
        'fuel-type' => 'fuel_type',
        'lifting-capacity' => 'lift_capacity',
        'transmission-type' => 'transmission',
        'power-steering' => 'power_steering',
        'ac-cabin' => 'ac_cabin',
    ];

    public function rebuild(Product $product): void
    {
        $product->loadMissing('specValues.attribute');

        $row = [
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'price' => $product->price_min,
            'status' => $product->status,
            'hp' => $product->hp_min,
            'wheel_drive' => null,
            'cylinders' => null,
            'fuel_type' => null,
            'lift_capacity' => null,
            'transmission' => null,
            'power_steering' => false,
            'ac_cabin' => false,
        ];

        foreach ($product->specValues as $value) {
            $column = self::MAP[$value->attribute?->slug] ?? null;

            if (! $column) {
                continue;
            }

            $row[$column] = match ($column) {
                'hp', 'lift_capacity' => $value->value_number ?? $row[$column],
                'cylinders' => $value->value_number !== null ? (int) $value->value_number : $row[$column],
                'power_steering', 'ac_cabin' => (bool) ($value->value_boolean ?? $this->truthy($value->value_string)),
                default => $value->value_string ?? $row[$column],
            };
        }

        ProductFilterCache::updateOrCreate(['product_id' => $product->id], $row);

        $this->flushFacets();
    }

    public function rebuildAll(): int
    {
        $count = 0;

        Product::with('specValues.attribute')->chunkById(200, function ($products) use (&$count) {
            foreach ($products as $product) {
                $this->rebuild($product);
                $count++;
            }
        });

        return $count;
    }

    public function forget(Product $product): void
    {
        ProductFilterCache::where('product_id', $product->id)->delete();
        $this->flushFacets();
    }

    /**
     * Attributes that can drive a facet. Cached because the definition changes
     * rarely but is read on every listing request.
     */
    public function filterableAttributes(): Collection
    {
        // Cache ids only; hydrating models from a serialised cache entry is fragile.
        $ids = Cache::remember('catalog.filterable_attribute_ids', now()->addDay(),
            fn () => SpecAttribute::filterable()->orderBy('sort_order')->pluck('id')->all());

        return SpecAttribute::whereIn('id', $ids)->orderBy('sort_order')->get();
    }

    public function flushFacets(): void
    {
        Cache::forget('catalog.filterable_attribute_ids');

        // Facet counts are keyed per filter combination; the tag-less cache
        // drivers we support cannot flush selectively, so we version the key.
        Cache::increment('catalog.facet_version');
    }

    public static function facetVersion(): int
    {
        return (int) (Cache::get('catalog.facet_version') ?? 1);
    }

    private function truthy(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['yes', 'true', '1', 'available'], true);
    }
}
