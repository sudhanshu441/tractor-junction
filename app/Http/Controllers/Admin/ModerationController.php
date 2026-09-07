<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Marketplace\Services\ListingService;
use App\Domain\Marketplace\Services\ValuationService;
use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Models\UsedListing;
use App\Models\UsedListingImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The highest-volume admin job: one listing on screen at a time, with the
 * checks a moderator actually performs, and A / R / arrow keys to work it.
 */
class ModerationController extends Controller
{
    public function __construct(
        private readonly ListingService $listings,
        private readonly ValuationService $valuation,
    ) {}

    public function queue(Request $request): View
    {
        $queue = UsedListing::pendingReview()
            ->with(['images', 'brand', 'product', 'seller', 'city', 'district', 'state'])
            ->get();

        $position = max(1, min((int) $request->integer('position', 1), max(1, $queue->count())));
        $listing = $queue->get($position - 1);

        return view('admin.moderation.queue', [
            'listing' => $listing,
            'position' => $position,
            'total' => $queue->count(),
            'valuation' => $listing ? $this->valuation->estimate($listing) : null,
            'assessment' => $listing ? $this->valuation->assess($listing) : null,
            'sellerHistory' => $listing ? $this->sellerHistory($listing) : null,
            'duplicateHashes' => $listing ? $this->duplicateImageHashes($listing) : collect(),
            'oldestWait' => $queue->first()?->created_at,
            'slaHours' => config('kj.listings.moderation_sla_hours'),
        ]);
    }

    public function decide(Request $request, UsedListing $listing): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,request_changes,block'],
            'reason' => ['required_unless:decision,approve', 'nullable', 'string', 'max:500'],
        ], [
            'reason.required_unless' => __('A reason is required — the seller sees it verbatim.'),
        ]);

        try {
            $listing = match ($data['decision']) {
                'approve' => $this->listings->approve($listing, $request->string('reason')->toString() ?: null),
                'reject' => $this->listings->reject($listing, $data['reason']),
                'request_changes' => $this->listings->requestChanges($listing, $data['reason']),
                'block' => $this->listings->block($listing, $data['reason']),
            };
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        activity()->performedOn($listing)->causedBy($request->user())
            ->withProperties(['decision' => $data['decision'], 'reason' => $data['reason'] ?? null])
            ->log('Moderated listing');

        return response()->json([
            'status' => 'ok',
            'message' => match ($data['decision']) {
                'approve' => __('Approved — the listing is live.'),
                'reject' => __('Rejected. The seller has been told why.'),
                'request_changes' => __('Changes requested.'),
                'block' => __('Listing blocked.'),
            },
            'data' => ['remaining' => UsedListing::pendingReview()->count()],
        ]);
    }

    /** Bulk approve, for a batch a moderator has already eyeballed. */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'listing_ids' => ['required', 'array', 'max:50'],
            'listing_ids.*' => ['integer', 'exists:used_listings,id'],
        ]);

        $approved = 0;

        foreach (UsedListing::whereIn('id', $data['listing_ids'])->where('status', 'pending')->get() as $listing) {
            $this->listings->approve($listing, __('Bulk approved'));
            $approved++;
        }

        activity()->causedBy($request->user())->withProperties(['count' => $approved])->log('Bulk approved listings');

        return back()->with('success', trans_choice(':count listing approved|:count listings approved', $approved, ['count' => $approved]));
    }

    public function reports(Request $request): View
    {
        return view('admin.moderation.reports', [
            'reports' => ListingReport::with(['listing.brand', 'reporter'])
                ->open()->latest()->paginate(25),
        ]);
    }

    public function resolveReport(Request $request, ListingReport $report): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:dismiss,block_listing'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['action'] === 'block_listing' && $report->listing) {
            $this->listings->block($report->listing, $data['remarks'] ?: __('Blocked after a user report'));
        }

        $report->update([
            'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => $data['action'] === 'dismiss' ? __('Report dismissed.') : __('Listing blocked.'),
        ]);
    }

    /** All listings, for search and spot checks outside the queue. */
    public function index(Request $request): View
    {
        return view('admin.moderation.index');
    }

    public function data(Request $request): JsonResponse
    {
        $query = UsedListing::with(['brand', 'seller', 'city']);

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(fn ($q) => $q
                ->where('reference_no', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $total = UsedListing::count();
        $filtered = (clone $query)->count();

        $canSeeContact = $request->user()->can('leads.view_contact');

        $rows = $query->latest()
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (UsedListing $l) => [
                'reference' => $l->reference_no,
                'title' => $l->title ?: $l->reference_no,
                'seller' => $l->seller?->name ?? '—',
                // Contact numbers stay masked unless the role is allowed to see them.
                'mobile' => $canSeeContact ? $l->seller?->mobile : $l->seller?->masked_mobile,
                'city' => $l->city?->name ?? '—',
                'price' => '₹'.number_format((float) $l->expected_price),
                'status' => $l->status,
                'views' => $l->view_count,
                'leads' => $l->lead_count,
                'created' => $l->created_at->diffForHumans(),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    private function sellerHistory(UsedListing $listing): array
    {
        $sellerId = $listing->user_id;

        if (! $sellerId) {
            return ['total' => 0, 'approved' => 0, 'rejected' => 0, 'member_since' => null];
        }

        $listings = UsedListing::where('user_id', $sellerId);

        return [
            'total' => (clone $listings)->count(),
            'approved' => (clone $listings)->whereIn('status', ['live', 'sold', 'expired'])->count(),
            'rejected' => (clone $listings)->whereIn('status', ['rejected', 'blocked'])->count(),
            'member_since' => $listing->seller?->created_at,
        ];
    }

    /** Photos already used on another listing are the clearest fraud signal. */
    private function duplicateImageHashes(UsedListing $listing): Collection
    {
        $hashes = $listing->images->pluck('image_hash')->filter();

        if ($hashes->isEmpty()) {
            return collect();
        }

        return UsedListingImage::whereIn('image_hash', $hashes)
            ->where('used_listing_id', '!=', $listing->id)
            ->with('listing:id,reference_no')
            ->get()
            ->pluck('listing.reference_no')
            ->filter()
            ->unique()
            ->values();
    }
}
