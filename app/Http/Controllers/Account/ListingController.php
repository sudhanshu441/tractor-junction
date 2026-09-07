<?php

namespace App\Http\Controllers\Account;

use App\Domain\Marketplace\Services\ListingService;
use App\Domain\Marketplace\Services\ValuationService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\UsedListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function __construct(
        private readonly ListingService $listings,
        private readonly ValuationService $valuation,
    ) {}

    public function index(Request $request): View
    {
        return view('account.listings.index', [
            'listings' => UsedListing::with(['images', 'brand', 'city'])
                ->where('user_id', $request->user()->id)
                ->latest()->paginate(12),
        ]);
    }

    public function show(Request $request, UsedListing $listing): View
    {
        $this->authorizeOwner($request, $listing);

        return view('account.listings.show', [
            'listing' => $listing->load(['images', 'brand', 'product', 'statusLogs.changedBy', 'inspection']),
            'valuation' => $this->valuation->estimate($listing),
            'leads' => Lead::where('leadable_type', $listing->getMorphClass())
                ->where('leadable_id', $listing->id)
                ->with('activities')
                ->latest()->get(),
        ]);
    }

    public function markSold(Request $request, UsedListing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        try {
            $this->listings->markSold($listing);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok', 'message' => __('Marked as sold. Congratulations!')]);
    }

    public function renew(Request $request, UsedListing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        $this->listings->renew($listing);

        return response()->json([
            'status' => 'ok',
            'message' => __('Renewed for :days more days.', ['days' => config('kj.listings.expiry_days')]),
        ]);
    }

    /** Leads on all of this seller's listings, newest first. */
    public function leads(Request $request): View
    {
        $listingIds = UsedListing::where('user_id', $request->user()->id)->pluck('id');

        return view('account.leads', [
            'leads' => Lead::with(['leadable', 'city'])
                ->where('leadable_type', (new UsedListing)->getMorphClass())
                ->whereIn('leadable_id', $listingIds)
                ->latest()->paginate(20),
        ]);
    }

    /** Enquiries this user sent about other people's machinery. */
    public function enquiries(Request $request): View
    {
        return view('account.enquiries', [
            'leads' => Lead::with(['leadable', 'currentAssignment.dealer'])
                ->where('user_id', $request->user()->id)
                ->latest()->paginate(20),
        ]);
    }

    private function authorizeOwner(Request $request, UsedListing $listing): void
    {
        abort_unless($listing->user_id === $request->user()->id, 403);
    }
}
