<?php

namespace App\Http\Controllers\Ajax;

use App\Domain\Auth\OtpService;
use App\Domain\Lead\Services\LeadService;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\UsedListing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Enquiry capture. Short form, OTP-verified, then routed.
 *
 * The OTP is the anti-spam mechanism and the quality guarantee: a dealer is
 * only ever charged for a number someone actually holds.
 */
class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leads,
        private readonly OtpService $otp,
    ) {}

    /** Issue the code that will verify this enquiry. */
    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
        ]);

        $result = $this->otp->send($data['mobile'], 'lead', $request->ip());

        if (! $result['sent']) {
            return response()->json([
                'status' => 'error',
                'message' => __('Too many codes requested. Please try again shortly.'),
            ], 429);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('Code sent to :mobile.', ['mobile' => $data['mobile']]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:new_product,used_listing,dealer,callback,offer,contact'],
            'about_type' => ['nullable', 'in:product,used_listing,dealer'],
            'about_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'digits_between:4,8'],
            'email' => ['nullable', 'email', 'max:150'],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'message' => ['nullable', 'string', 'max:1000'],
            'financing_needed' => ['nullable', 'boolean'],
        ]);

        $verification = $this->otp->verify($data['mobile'], $data['otp'], 'lead');

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

        $about = $this->resolveSubject($data);

        $lead = $this->leads->capture($data['type'], [
            ...$data,
            'mobile_verified' => true,
            'meta' => array_filter([
                'financing_needed' => $data['financing_needed'] ?? null,
                'utm_source' => $request->query('utm_source'),
            ], fn ($v) => $v !== null),
            'utm_source' => $request->query('utm_source'),
            'channel' => $request->hasHeader('X-Requested-With') ? 'web' : 'web',
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], $about, $request->user());

        return response()->json([
            'status' => 'ok',
            'message' => $lead->status === 'duplicate'
                ? __('You have already enquired about this. Our team will call you.')
                : __('Thank you. Your enquiry :reference is registered and someone will call you shortly.', [
                    'reference' => $lead->reference_no,
                ]),
            'data' => ['reference' => $lead->reference_no],
        ]);
    }

    /**
     * Reveals a seller's number only after the buyer verifies their own, and
     * records the reveal as a lead both sides can see.
     */
    public function revealContact(Request $request, UsedListing $listing): JsonResponse
    {
        abort_unless($listing->status === 'live', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'digits_between:4,8'],
        ]);

        $verification = $this->otp->verify($data['mobile'], $data['otp'], 'lead');

        if (! $verification['verified']) {
            return response()->json(['status' => 'error', 'message' => __('That code is not correct or has expired.')], 422);
        }

        $lead = $this->leads->capture('used_listing', [
            ...$data,
            'mobile_verified' => true,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], $listing, $request->user());

        $listing->incrementQuietly('lead_count');

        $sellerMobile = $listing->seller?->mobile ?? $listing->dealer?->mobile;

        return response()->json([
            'status' => 'ok',
            'message' => __('Here is the seller\'s number. They have been told you are interested.'),
            'data' => [
                'mobile' => $sellerMobile,
                'seller_name' => $listing->seller?->name ?? $listing->dealer?->display_name,
                'reference' => $lead->reference_no,
            ],
        ]);
    }

    /** @return Model|null the Product, UsedListing or Dealer this is about */
    private function resolveSubject(array $data): ?Model
    {
        if (empty($data['about_type']) || empty($data['about_id'])) {
            return null;
        }

        return match ($data['about_type']) {
            'product' => Product::find($data['about_id']),
            'used_listing' => UsedListing::find($data['about_id']),
            'dealer' => Dealer::find($data['about_id']),
            default => null,
        };
    }
}
