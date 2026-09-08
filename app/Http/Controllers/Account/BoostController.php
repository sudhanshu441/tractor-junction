<?php

namespace App\Http\Controllers\Account;

use App\Domain\Billing\Services\BillingService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\UsedListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Paid promotion for one used listing.
 *
 * Only a live listing can be boosted — paying to promote something buyers
 * cannot open would be taking money for nothing.
 */
class BoostController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function show(Request $request, UsedListing $listing): View
    {
        $this->authorizeOwner($request, $listing);

        return view('account.listings.boost', [
            'listing' => $listing->load('images'),
            'plans' => Plan::where('audience', 'seller')->where('is_active', true)
                ->orderBy('sort_order')->get(),
            'running' => $listing->boosts()->running()->with('plan')->first(),
        ]);
    }

    public function checkout(Request $request, UsedListing $listing, Plan $plan): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        if ($listing->status !== 'live') {
            return response()->json([
                'status' => 'error',
                'message' => __('Only a live listing can be promoted. This one is :status.', ['status' => $listing->status]),
            ], 422);
        }

        if ($plan->audience !== 'seller' || ! $plan->is_active) {
            return response()->json(['status' => 'error', 'message' => __('That package is not available.')], 422);
        }

        if ($listing->boosts()->running()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => __('This listing is already promoted. Wait for the current package to finish.'),
            ], 422);
        }

        $result = $this->billing->boost($listing, $plan, $request->user());

        if (! $result['payment']) {
            return response()->json([
                'status' => 'ok',
                'message' => __('Your listing is promoted.'),
                'data' => ['paid' => true],
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('Order created.'),
            'data' => [
                'paid' => false,
                'reference' => $result['payment']->reference_no,
                'confirm_url' => route('account.boost.confirm', [$listing, $result['payment']]),
                'checkout' => $result['checkout'],
                'key_id' => config('kj.payments.razorpay.key_id'),
            ],
        ]);
    }

    public function confirm(Request $request, UsedListing $listing, Payment $payment): JsonResponse
    {
        $this->authorizeOwner($request, $listing);
        abort_unless($payment->user_id === $request->user()->id, 403);

        $settled = $this->billing->settle($payment, $request->except('_token'));

        return response()->json([
            'status' => $settled ? 'ok' : 'error',
            'message' => $settled
                ? __('Payment received — your listing is promoted.')
                : __('We could not verify that payment. Your listing has not been charged.'),
        ], $settled ? 200 : 422);
    }

    private function authorizeOwner(Request $request, UsedListing $listing): void
    {
        abort_unless($listing->user_id === $request->user()->id, 403);
    }
}
