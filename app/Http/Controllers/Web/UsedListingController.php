<?php

namespace App\Http\Controllers\Web;

use App\Domain\Marketplace\Filters\UsedListingFilter;
use App\Domain\Marketplace\Services\ValuationService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\District;
use App\Models\State;
use App\Models\UsedListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsedListingController extends Controller
{
    public function __construct(private readonly ValuationService $valuation) {}

    public function index(Request $request, ?string $stateSlug = null, ?string $districtSlug = null): View
    {
        $state = $stateSlug ? State::active()->where('slug', $stateSlug)->firstOrFail() : null;
        $district = $districtSlug && $state
            ? District::where('state_id', $state->id)->where('slug', $districtSlug)->firstOrFail()
            : null;

        if ($state) {
            $request->merge(['state' => [$state->id]]);
        }

        if ($district) {
            $request->merge(['district' => [$district->id]]);
        }

        $filter = UsedListingFilter::fromRequest($request);

        return view('web.used.index', [
            'listings' => $filter->apply($this->baseQuery())->paginate(24)->withQueryString(),
            'filter' => $filter,
            'activeFilters' => $filter->activeFilters(),
            'facets' => $this->facets($state, $district),
            'state' => $state,
            'district' => $district,
            'heading' => $this->heading($state, $district),
        ]);
    }

    public function show(string $slug): View
    {
        $listing = UsedListing::with([
            'images', 'brand', 'category', 'product.specValues.attribute.group',
            'state', 'district', 'city', 'seller', 'dealer', 'inspection',
        ])->where('slug', $slug)->firstOrFail();

        abort_unless($listing->status === 'live', 404);

        $listing->incrementQuietly('view_count');

        return view('web.used.show', [
            'listing' => $listing,
            'valuation' => $this->valuation->estimate($listing),
            'similar' => $this->similar($listing),
            'keySpecs' => $listing->product?->specValues
                ->filter(fn ($v) => $v->attribute?->is_key_spec)
                ->sortBy(fn ($v) => $v->attribute->sort_order) ?? collect(),
        ]);
    }

    /** AJAX grid refresh — returns the same partial the page renders. */
    public function filter(Request $request): JsonResponse
    {
        $filter = UsedListingFilter::fromRequest($request);
        $listings = $filter->apply($this->baseQuery())->paginate(24)->withQueryString();

        return response()->json([
            'status' => 'ok',
            'data' => [
                'total' => $listings->total(),
                'summary' => trans_choice(':count listing|:count listings', $listings->total(),
                    ['count' => number_format($listings->total())]),
                'grid' => view('partials.ajax.used-grid', ['listings' => $listings])->render(),
                'pagination' => $listings->onEachSide(1)->links()->toHtml(),
                'query' => http_build_query($filter->activeFilters()),
            ],
        ]);
    }

    private function baseQuery()
    {
        return UsedListing::query()->with(['images', 'brand', 'product', 'city', 'district']);
    }

    private function facets(?State $state, ?District $district): array
    {
        $base = fn () => UsedListing::query()->live()
            ->when($state, fn ($q) => $q->where('state_id', $state->id))
            ->when($district, fn ($q) => $q->where('district_id', $district->id));

        return [
            'brands' => Brand::active()
                ->whereIn('id', (clone $base())->distinct()->pluck('brand_id'))
                ->orderBy('name')->get(['id', 'name', 'slug'])
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'count' => (clone $base())->where('brand_id', $b->id)->count(),
                ])->all(),
            'categories' => Category::active()
                ->whereIn('id', (clone $base())->distinct()->pluck('category_id'))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'count' => (clone $base())->where('category_id', $c->id)->count(),
                ])->all(),
            'conditions' => collect(['excellent', 'good', 'average', 'needs_repair'])
                ->map(fn ($c) => [
                    'value' => $c,
                    'label' => ucfirst(str_replace('_', ' ', $c)),
                    'count' => (clone $base())->where('condition', $c)->count(),
                ])->all(),
            'verified' => (clone $base())->where('is_verified', true)->count(),
            'total' => $base()->count(),
        ];
    }

    private function similar(UsedListing $listing)
    {
        return UsedListing::with(['images', 'brand', 'city'])
            ->live()
            ->where('id', '!=', $listing->id)
            ->where('brand_id', $listing->brand_id)
            ->when($listing->district_id, fn ($q) => $q->orderByRaw(
                'CASE WHEN district_id = ? THEN 0 ELSE 1 END', [$listing->district_id],
            ))
            ->limit(4)->get();
    }

    private function heading(?State $state, ?District $district): string
    {
        if ($district) {
            return __('Second-hand tractors in :district, :state', [
                'district' => $district->name, 'state' => $state->name,
            ]);
        }

        if ($state) {
            return __('Second-hand tractors in :state', ['state' => $state->name]);
        }

        return __('Used tractors & machinery');
    }
}
