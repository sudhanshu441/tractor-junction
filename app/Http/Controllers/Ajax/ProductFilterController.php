<?php

namespace App\Http\Controllers\Ajax;

use App\Domain\Catalog\Filters\ProductFilter;
use App\Domain\Catalog\Services\CompareService;
use App\Domain\Catalog\Services\FacetService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Re-renders the listing grid for the in-page filters.
 *
 * It returns the same Blade partial the full page uses, so filtered results are
 * identical whether they arrive by AJAX or by a direct hit on the URL.
 */
class ProductFilterController extends Controller
{
    public function __construct(
        private readonly FacetService $facets,
        private readonly CompareService $compare,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString() ?: 'tractor';
        $filter = ProductFilter::fromRequest($request);

        $products = $filter->apply(
            Product::query()->with(['brand', 'category', 'media'])
                ->whereHas('category', fn ($q) => $q->where('type', $type))
        )->paginate(24)->withQueryString();

        $facets = $this->facets->for(null, $filter->activeFilters(), $type);

        return response()->json([
            'status' => 'ok',
            'data' => [
                'total' => $products->total(),
                'summary' => trans_choice(':count model|:count models', $products->total(), ['count' => number_format($products->total())]),
                'grid' => view('partials.ajax.product-grid', [
                    'products' => $products,
                    'compareIds' => $this->compare->ids(),
                ])->render(),
                'pagination' => $products->onEachSide(1)->links()->toHtml(),
                'facets_html' => view('web.products.partials.filters', [
                    'facets' => $facets,
                ])->render(),
                'query' => http_build_query($filter->activeFilters()),
            ],
        ]);
    }
}
