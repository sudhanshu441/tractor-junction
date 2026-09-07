<?php

namespace App\Http\Controllers\Web;

use App\Domain\Auth\OtpService;
use App\Domain\Marketplace\Services\ListingService;
use App\Domain\Marketplace\Services\ValuationService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\State;
use App\Models\UsedListing;
use App\Models\UsedListingImage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Six-step sell wizard, built for a phone in a field.
 *
 * Every step POSTs and is saved server-side as a draft, so a dropped connection
 * loses at most the step in progress. The draft id lives in the session, which
 * is why a guest can start before they have an account.
 */
class SellController extends Controller
{
    private const SESSION_KEY = 'kj.sell.draft';

    public function __construct(
        private readonly ListingService $listings,
        private readonly ValuationService $valuation,
        private readonly OtpService $otp,
    ) {}

    public function start(Request $request): View
    {
        $draft = $this->currentDraft($request);

        return view('web.sell.wizard', [
            'draft' => $draft,
            'step' => $draft?->category_id ? 2 : 1,
            'categories' => Category::active()->whereIn('type', ['tractor', 'implement', 'harvester'])
                ->whereNull('parent_id')->orderBy('sort_order')->get(),
            'brands' => Brand::active()->orderBy('name')->get(),
            'states' => State::active()->orderBy('name')->get(),
            'minPhotos' => config('kj.listings.min_photos'),
            'maxPhotos' => config('kj.listings.max_photos'),
        ]);
    }

    /** Step 1–5: validate, save the draft, hand back what the next step needs. */
    public function saveStep(Request $request, int $step): JsonResponse
    {
        $draft = $this->currentDraft($request);

        $data = $this->validateStep($request, $step, $draft);

        $draft = $this->listings->saveDraft($data, $draft, Auth::id());
        $request->session()->put(self::SESSION_KEY, $draft->id);

        return response()->json([
            'status' => 'ok',
            'message' => __('Saved.'),
            'data' => [
                'draft_id' => $draft->id,
                'reference' => $draft->reference_no,
                'next_step' => $step + 1,
                'photos' => $draft->images()->count(),
                'valuation' => $step >= 3 ? $this->valuation->estimate($draft->load('state')) : null,
            ],
        ]);
    }

    /** Models for the chosen brand, so step 2 can narrow without a page load. */
    public function models(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $type = Category::find($data['category_id'] ?? null)?->type ?? 'tractor';

        $models = Product::active()
            ->where('brand_id', $data['brand_id'])
            ->whereHas('category', fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'hp_min']);

        return response()->json(['status' => 'ok', 'data' => $models]);
    }

    /** One photo per request: kinder to a weak connection than a single big POST. */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $draft = $this->currentDraft($request);

        if (! $draft) {
            return response()->json(['status' => 'error', 'message' => __('Start the form before adding photos.')], 422);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'angle' => ['nullable', 'in:front,rear,left,right,engine,tyre,meter,document,other'],
        ]);

        $max = (int) config('kj.listings.max_photos');

        if ($draft->images()->count() >= $max) {
            return response()->json([
                'status' => 'error',
                'message' => __('You can add up to :max photos.', ['max' => $max]),
            ], 422);
        }

        $file = $request->file('photo');
        $path = $file->store('used/'.now()->format('Y/m').'/'.$draft->reference_no, 'public');

        $image = UsedListingImage::create([
            'used_listing_id' => $draft->id,
            'path' => $path,
            'angle' => $request->input('angle', 'other'),
            'is_primary' => ! $draft->images()->where('is_primary', true)->exists(),
            // Cheap duplicate signal: the same file re-posted across listings.
            'image_hash' => hash_file('sha256', $file->getRealPath()),
            'sort_order' => $draft->images()->count(),
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => __('Photo added.'),
            'data' => [
                'id' => $image->id,
                'url' => $image->thumbnailUrl(),
                'count' => $draft->images()->count(),
            ],
        ]);
    }

    public function deletePhoto(Request $request, UsedListingImage $image): JsonResponse
    {
        $draft = $this->currentDraft($request);

        abort_unless($draft && $image->used_listing_id === $draft->id, 403);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json([
            'status' => 'ok',
            'message' => __('Photo removed.'),
            'data' => ['count' => $draft->images()->count()],
        ]);
    }

    /** Step 6: verify the seller's mobile, then submit for moderation. */
    public function submit(Request $request): JsonResponse
    {
        $draft = $this->currentDraft($request);

        if (! $draft) {
            return response()->json(['status' => 'error', 'message' => __('Your draft has expired. Please start again.')], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'digits_between:4,8'],
        ]);

        $minPhotos = (int) config('kj.listings.min_photos');

        if ($draft->images()->count() < $minPhotos) {
            return response()->json([
                'status' => 'error',
                'message' => __('Add at least :count photos before submitting.', ['count' => $minPhotos]),
            ], 422);
        }

        $verification = $this->otp->verify($data['mobile'], $data['otp'], 'listing');

        if (! $verification['verified']) {
            return response()->json([
                'status' => 'error',
                'message' => match ($verification['reason']) {
                    'incorrect' => __('That code is not correct.'),
                    'too_many_attempts' => __('Too many wrong attempts. Request a new code.'),
                    default => __('That code has expired. Request a new one.'),
                },
            ], 422);
        }

        $duplicate = $this->listings->findRecentDuplicate(
            $data['mobile'], $draft->product_id, $draft->brand_id, $draft->id,
        );

        if ($duplicate) {
            return response()->json([
                'status' => 'error',
                'message' => __('You already have a live listing for this machine (:reference). Edit that one instead.', [
                    'reference' => $duplicate->reference_no,
                ]),
                'data' => ['duplicate_of' => $duplicate->reference_no],
            ], 422);
        }

        $seller = $this->resolveSeller($data, $request);

        DB::transaction(function () use ($draft, $seller) {
            $draft->forceFill(['user_id' => $seller->id])->save();
            $this->listings->submit($draft);
        });

        $request->session()->forget(self::SESSION_KEY);

        return response()->json([
            'status' => 'ok',
            'message' => __('Your listing is submitted for review.'),
            'data' => [
                'reference' => $draft->reference_no,
                'redirect' => route('sell.submitted', $draft->reference_no),
            ],
        ]);
    }

    public function submitted(string $reference): View
    {
        $listing = UsedListing::where('reference_no', $reference)->firstOrFail();

        return view('web.sell.submitted', [
            'listing' => $listing,
            'valuation' => $this->valuation->estimate($listing->load('state')),
            'slaHours' => config('kj.listings.moderation_sla_hours'),
        ]);
    }

    // ----- internals -----

    private function currentDraft(Request $request): ?UsedListing
    {
        $id = $request->session()->get(self::SESSION_KEY);

        if (! $id) {
            return null;
        }

        return UsedListing::with('images')->where('id', $id)->where('status', 'draft')->first();
    }

    private function validateStep(Request $request, int $step, ?UsedListing $draft): array
    {
        return match ($step) {
            1 => $request->validate([
                'category_id' => ['required', 'exists:categories,id'],
            ]),
            2 => $request->validate([
                'brand_id' => ['required', 'exists:brands,id'],
                'product_id' => ['nullable', 'exists:products,id'],
                'manufacturing_year' => ['required', 'integer', 'min:1980', 'max:'.date('Y')],
            ]),
            3 => $request->validate([
                'engine_hours' => ['nullable', 'integer', 'min:0', 'max:99999'],
                'hp' => ['nullable', 'numeric', 'min:0', 'max:999'],
                'condition' => ['required', 'in:excellent,good,average,needs_repair'],
                'tyre_condition_front' => ['nullable', 'in:new,good,worn'],
                'tyre_condition_rear' => ['nullable', 'in:new,good,worn'],
                'has_rc' => ['boolean'],
                'has_insurance' => ['boolean'],
                'is_financed' => ['boolean'],
            ]),
            4 => [], // photos are uploaded individually
            5 => $request->validate([
                'expected_price' => ['required', 'numeric', 'min:5000', 'max:99999999'],
                'is_price_negotiable' => ['boolean'],
                'description' => ['nullable', 'string', 'max:2000'],
                'state_id' => ['required', 'exists:states,id'],
                'district_id' => ['required', 'exists:districts,id'],
                'city_id' => ['nullable', 'exists:cities,id'],
                'pincode' => ['nullable', 'digits:6'],
            ]),
            default => abort(404),
        };
    }

    /** A seller who is already signed in keeps their account; a guest gets one. */
    private function resolveSeller(array $data, Request $request): User
    {
        if ($user = $request->user()) {
            return $user;
        }

        $user = User::where('mobile', $data['mobile'])->first();

        if (! $user) {
            $user = User::create([
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'mobile_verified_at' => now(),
                'user_type' => 'customer',
                'is_active' => true,
            ]);
            $user->assignRole('customer');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $user;
    }
}
