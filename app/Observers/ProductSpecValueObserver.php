<?php

namespace App\Observers;

use App\Domain\Catalog\Services\FilterCacheService;
use App\Models\ProductSpecValue;

/** A spec change must reach the filter cache, not just the product row. */
class ProductSpecValueObserver
{
    public function __construct(private readonly FilterCacheService $filterCache) {}

    public function saved(ProductSpecValue $value): void
    {
        $this->rebuild($value);
    }

    public function deleted(ProductSpecValue $value): void
    {
        $this->rebuild($value);
    }

    private function rebuild(ProductSpecValue $value): void
    {
        $product = $value->product()->with('specValues.attribute')->first();

        if ($product) {
            $this->filterCache->rebuild($product);
        }
    }
}
