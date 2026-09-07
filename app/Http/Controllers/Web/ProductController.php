<?php

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\Filters\ProductFilter;
use App\Domain\Catalog\Services\CompareService;
use App\Domain\Catalog\Services\FacetService;
use App\Domain\Catalog\Services\PriceService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Public catalogue. Every action returns a full server-rendered page — AJAX
 * filtering re-renders the same partial this controller uses, so a shared URL
 * and an in-page filter can never disagree.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly FacetService $facets,
        private readonly PriceService $prices,
        private readonly CompareService $compare,
    ) {}

    /** Listing for a machinery type: /tractors, /implements, /harvesters… */
    public function index(Request $request, string $type = 'tractor'): View
    {
        $filter = ProductFilter::fromRequest($request);

        $products = $filter->apply(
            Product::query()->with(['brand', 'category', 'media'])
                ->whereHas('category', fn ($q) => $q->where('type', $type))
        )->paginate(24)->withQueryString();

        return view('web.products.index', [
            'type' => $type,
            'heading' => $this->headingFor($type),
            'products' => $products,
            'facets' => $this->facets->for(null, $filter->activeFilters(), $type),
            'filter' => $filter,
            'activeFilters' => $filter->activeFilters(),
            'compareIds' => $this->compare->ids(),
            'categories' => Category::active()->where('type', $type)->orderBy('sort_order')->get(),
        ]);
    }

    /** Model detail: /tractors/mahindra/575-di-xp-plus */
    public function show(Request $request, string $brandSlug, string $productSlug): View
    {
        $brand = Brand::active()->where('slug', $brandSlug)->firstOrFail();

        $product = Product::with([
            'brand', 'category', 'media', 'variants',
            'specValues.attribute.group', 'features', 'faqs', 'videos',
            'competitors.competitorProduct.brand', 'prices.state',
        ])->active()->where('brand_id', $brand->id)->where('slug', $productSlug)->firstOrFail();

        // A view counter must never block the render or trip a race.
        $product->incrementQuietly('view_count');

        $stateId = $request->user()?->state_id ?? $request->integer('state') ?: null;

        return view('web.products.show', [
            'product' => $product,
            'brand' => $brand,
            'price' => $this->prices->resolve($product, $stateId),
            'statePrices' => $this->prices->stateTable($product),
            'states' => State::active()->orderBy('name')->get(),
            'specGroups' => $product->specValues
                ->filter(fn ($v) => $v->attribute !== null)
                ->groupBy(fn ($v) => $v->attribute->group?->name ?? __('Other')),
            'keySpecs' => $product->specValues
                ->filter(fn ($v) => $v->attribute?->is_key_spec)
                ->sortBy(fn ($v) => $v->attribute->sort_order),
            'competitors' => $product->competitors->pluck('competitorProduct')->filter(),
            'similar' => $this->similar($product),
            'compareIds' => $this->compare->ids(),
        ]);
    }

    /** Brand landing: /tractors/brand/mahindra */
    public function brand(Request $request, string $type, string $brandSlug): View
    {
        $brand = Brand::active()->where('slug', $brandSlug)->firstOrFail();
        $filter = ProductFilter::fromRequest($request);

        $products = $filter->apply(
            Product::query()->with(['brand', 'category', 'media'])
                ->where('products.brand_id', $brand->id)
                ->whereHas('category', fn ($q) => $q->where('type', $type))
        )->paginate(24)->withQueryString();

        return view('web.products.index', [
            'type' => $type,
            'heading' => __(':brand :type in India', ['brand' => $brand->name, 'type' => $this->headingFor($type)]),
            'brand' => $brand,
            'products' => $products,
            'facets' => $this->facets->for(null, $filter->activeFilters(), $type),
            'filter' => $filter,
            'activeFilters' => $filter->activeFilters(),
            'compareIds' => $this->compare->ids(),
            'categories' => Category::active()->where('type', $type)->orderBy('sort_order')->get(),
        ]);
    }

    /** HP or budget band: /tractors/hp/40-50-hp, /tractors/price/under-5-lakh */
    public function band(Request $request, string $type, string $bandType, string $slug): View
    {
        $band = $this->facets->bandBySlug($bandType === 'hp' ? 'hp' : 'price', $slug);

        abort_if($band === null, 404);

        $key = $bandType === 'hp' ? 'hp' : 'price';
        $request->merge(["{$key}_min" => $band['min'], "{$key}_max" => $band['max']]);

        $filter = ProductFilter::fromRequest($request);

        $products = $filter->apply(
            Product::query()->with(['brand', 'category', 'media'])
                ->whereHas('category', fn ($q) => $q->where('type', $type))
        )->paginate(24)->withQueryString();

        return view('web.products.index', [
            'type' => $type,
            'heading' => __(':band tractors in India', ['band' => $band['label']]),
            'band' => $band,
            'products' => $products,
            'facets' => $this->facets->for(null, $filter->activeFilters(), $type),
            'filter' => $filter,
            'activeFilters' => $filter->activeFilters(),
            'compareIds' => $this->compare->ids(),
            'categories' => Category::active()->where('type', $type)->orderBy('sort_order')->get(),
        ]);
    }

    /** Curated collection: /tractors/popular, /tractors/latest, /tractors/upcoming */
    public function collection(Request $request, string $type, string $collection): View
    {
        $query = Product::query()->with(['brand', 'category', 'media'])
            ->whereHas('category', fn ($q) => $q->where('type', $type));

        $heading = match ($collection) {
            'popular' => __('Popular tractors in India'),
            'latest' => __('Latest tractors in India'),
            'upcoming' => __('Upcoming tractors in India'),
            default => abort(404),
        };

        match ($collection) {
            'popular' => $query->where('products.is_popular', true),
            'latest' => $query->orderByDesc('products.launch_year'),
            'upcoming' => $query->where('products.status', 'upcoming'),
        };

        $filter = ProductFilter::fromRequest($request);

        return view('web.products.index', [
            'type' => $type,
            'heading' => $heading,
            'products' => $filter->apply($query)->paginate(24)->withQueryString(),
            'facets' => $this->facets->for(null, $filter->activeFilters(), $type),
            'filter' => $filter,
            'activeFilters' => $filter->activeFilters(),
            'compareIds' => $this->compare->ids(),
            'categories' => Category::active()->where('type', $type)->orderBy('sort_order')->get(),
        ]);
    }

    /** State price list: /tractors/price-list/rajasthan */
    public function priceList(Request $request, string $stateSlug): View
    {
        $state = State::active()->where('slug', $stateSlug)->firstOrFail();

        $products = Product::with(['brand', 'prices' => fn ($q) => $q->where('is_active', true)])
            ->active()->available()
            ->whereHas('category', fn ($q) => $q->where('type', 'tractor'))
            ->orderByDesc('popularity_score')
            ->paginate(50);

        return view('web.products.price-list', [
            'state' => $state,
            'products' => $products,
            'priceService' => $this->prices,
            'states' => State::active()->orderBy('name')->get(),
        ]);
    }

    private function similar(Product $product, int $limit = 4): Collection
    {
        $hp = (float) ($product->hp_min ?? 0);

        return Product::with(['brand', 'media'])
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->when($hp > 0, fn ($q) => $q->whereBetween('hp_min', [$hp - 8, $hp + 8]))
            ->orderByDesc('popularity_score')
            ->limit($limit)
            ->get();
    }

    private function headingFor(string $type): string
    {
        return match ($type) {
            'implement' => __('Farm implements'),
            'harvester' => __('Harvesters'),
            'tyre' => __('Tractor tyres'),
            'farm_tool' => __('Farm tools'),
            default => __('Tractors'),
        };
    }
}
