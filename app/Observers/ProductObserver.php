<?php

namespace App\Observers;

use App\Domain\Catalog\Services\FilterCacheService;
use App\Models\Product;

class ProductObserver
{
    public function __construct(private readonly FilterCacheService $filterCache) {}

    public function saved(Product $product): void
    {
        $this->filterCache->rebuild($product);
    }

    public function deleted(Product $product): void
    {
        $this->filterCache->forget($product);
    }

    public function restored(Product $product): void
    {
        $this->filterCache->rebuild($product);
    }
}
