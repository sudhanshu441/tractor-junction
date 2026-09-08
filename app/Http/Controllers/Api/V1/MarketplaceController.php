<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Finance\Services\EmiCalculator;
use App\Domain\Marketplace\Filters\UsedListingFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\DealerResource;
use App\Http\Resources\UsedListingResource;
use App\Models\Dealer;
use App\Models\UsedListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function listings(Request $request): JsonResponse
    {
        $filter = UsedListingFilter::fromRequest($request);

        $listings = $filter->apply(
            UsedListing::query()->with(['brand', 'city', 'district', 'state', 'images'])->where('status', 'live')
        )->paginate(min(50, (int) $request->query('per_page', 20)));

        return response()->json([
            'status' => 'ok',
            'data' => UsedListingResource::collection($listings)->resolve(),
            'meta' => [
                'page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
            ],
        ]);
    }

    public function listing(string $slug): JsonResponse
    {
        $listing = UsedListing::with(['brand', 'product', 'city', 'district', 'state', 'images', 'seller', 'inspection'])
            ->where('slug', $slug)
            ->where('status', 'live')
            ->firstOrFail();

        $listing->incrementQuietly('view_count');

        return response()->json([
            'status' => 'ok',
            'data' => (new UsedListingResource($listing))->resolve(),
        ]);
    }

    public function dealers(Request $request): JsonResponse
    {
        $dealers = Dealer::verified()
            ->with(['brands', 'city', 'district', 'state'])
            ->when($request->query('state_id'), fn ($q, $id) => $q->where('state_id', $id))
            ->when($request->query('district_id'), fn ($q, $id) => $q->where('district_id', $id))
            ->when($request->query('brand_id'), fn ($q, $id) => $q->whereHas('brands', fn ($b) => $b->where('brands.id', $id)))
            ->orderByDesc('is_featured')
            ->orderByDesc('rating_avg')
            ->paginate(min(50, (int) $request->query('per_page', 20)));

        return response()->json([
            'status' => 'ok',
            'data' => DealerResource::collection($dealers)->resolve(),
            'meta' => [
                'page' => $dealers->currentPage(),
                'total' => $dealers->total(),
                'last_page' => $dealers->lastPage(),
            ],
        ]);
    }

    /** Public and stateless — an EMI figure is arithmetic, not an account feature. */
    public function emi(Request $request, EmiCalculator $emi): JsonResponse
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:1000', 'max:99999999'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:40'],
            'tenure_months' => ['required', 'integer', 'min:3', 'max:120'],
            'frequency' => ['nullable', 'in:monthly,quarterly,half_yearly,yearly'],
        ]);

        return response()->json([
            'status' => 'ok',
            'data' => $emi->calculate(
                (float) $data['price'],
                (float) ($data['down_payment'] ?? 0),
                (float) $data['interest_rate'],
                (int) $data['tenure_months'],
                $data['frequency'] ?? 'monthly',
            ),
        ]);
    }
}
