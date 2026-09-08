<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Filters\ProductFilter;
use App\Domain\Catalog\Services\FacetService;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only catalogue for the mobile app. Public — a farmer should be able to
 * browse prices before signing up for anything.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly FacetService $facets) {}

    public function products(Request $request): JsonResponse
    {
        $filter = ProductFilter::fromRequest($request);

        $products = $filter->apply(
            Product::query()->with(['brand', 'category', 'media'])
                ->when($request->query('type'), fn ($q, $type) => $q->whereHas('category', fn ($c) => $c->where('type', $type)))
        )->paginate(min(50, (int) $request->query('per_page', 20)));

        return response()->json([
            'status' => 'ok',
            'data' => ProductResource::collection($products)->resolve(),
            'meta' => [
                'page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function product(Request $request, string $brandSlug, string $productSlug): JsonResponse
    {
        $product = Product::with([
            'brand', 'category', 'media', 'specValues.attribute.group', 'features', 'variants',
        ])->active()
            ->whereHas('brand', fn ($q) => $q->where('slug', $brandSlug))
            ->where('slug', $productSlug)
            ->firstOrFail();

        $product->incrementQuietly('view_count');

        return response()->json([
            'status' => 'ok',
            'data' => (new ProductResource($product))->resolve(),
        ]);
    }

    public function brands(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => Brand::active()->orderBy('name')->get(['id', 'name', 'slug', 'logo'])
                ->map(fn (Brand $b) => [
                    'id' => $b->id, 'name' => $b->name, 'slug' => $b->slug,
                    'logo' => $b->logo ? asset('storage/'.$b->logo) : null,
                ]),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => Category::active()
                ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'type']),
        ]);
    }

    public function filters(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => $this->facets->for(null, [], $request->query('type', 'tractor')),
        ]);
    }
}
