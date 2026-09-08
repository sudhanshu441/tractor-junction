<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Lead\Services\LeadService;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsedListingResource;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Everything behind a token: the signed-in farmer's own listings, enquiries and
 * saved machines.
 */
class AccountController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function listings(Request $request): JsonResponse
    {
        $listings = UsedListing::with(['brand', 'city', 'images'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'ok',
            'data' => UsedListingResource::collection($listings)->resolve(),
            'meta' => ['total' => $listings->total(), 'page' => $listings->currentPage()],
        ]);
    }

    public function enquiries(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => Lead::where('user_id', $request->user()->id)
                ->with('leadable')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (Lead $lead) => [
                    'reference' => $lead->reference_no,
                    'type' => $lead->type,
                    'status' => $lead->status,
                    'about' => $lead->leadable?->title ?? $lead->leadable?->name ?? $lead->leadable?->display_name,
                    'created_at' => $lead->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * Enquiries from the app are already OTP-verified — the token proves the
     * number — so no second code is asked for here.
     */
    public function enquire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:new_product,used_listing,dealer,callback'],
            'about_type' => ['nullable', 'in:product,used_listing,dealer'],
            'about_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:1000'],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        $user = $request->user();

        $lead = $this->leads->capture($data['type'], [
            ...$data,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'mobile_verified' => true,
            'email' => $user->email,
            'channel' => 'app',
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], $this->subject($data), $user);

        return response()->json([
            'status' => 'ok',
            'message' => $lead->status === 'duplicate'
                ? __('You have already enquired about this. Our team will call you.')
                : __('Enquiry :reference registered.', ['reference' => $lead->reference_no]),
            'data' => ['reference' => $lead->reference_no],
        ]);
    }

    public function wishlist(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => Wishlist::where('user_id', $request->user()->id)
                ->with('wishable')
                ->latest()
                ->get()
                ->map(fn (Wishlist $item) => [
                    'id' => $item->id,
                    'type' => class_basename($item->wishable_type),
                    'name' => $item->wishable?->name ?? $item->wishable?->title ?? $item->wishable?->display_name,
                    'saved_at' => $item->created_at?->toIso8601String(),
                ])
                ->filter(fn ($row) => $row['name'] !== null)
                ->values(),
        ]);
    }

    public function saveToWishlist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:product,used_listing,dealer'],
            'id' => ['required', 'integer'],
        ]);

        $subject = $this->subject(['about_type' => $data['type'], 'about_id' => $data['id']]);

        if (! $subject) {
            return response()->json(['status' => 'error', 'message' => __('That item no longer exists.')], 404);
        }

        Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'wishable_type' => $subject->getMorphClass(),
            'wishable_id' => $subject->getKey(),
        ]);

        return response()->json(['status' => 'ok', 'message' => __('Saved.')]);
    }

    public function removeFromWishlist(Request $request, Wishlist $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return response()->json(['status' => 'ok', 'message' => __('Removed.')]);
    }

    private function subject(array $data): ?Model
    {
        return match ($data['about_type'] ?? null) {
            'product' => Product::find($data['about_id'] ?? $data['id'] ?? null),
            'used_listing' => UsedListing::find($data['about_id'] ?? $data['id'] ?? null),
            'dealer' => Dealer::find($data['about_id'] ?? $data['id'] ?? null),
            default => null,
        };
    }
}
