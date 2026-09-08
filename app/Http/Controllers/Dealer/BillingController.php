<?php

namespace App\Http\Controllers\Dealer;

use App\Domain\Billing\Services\BillingService;
use App\Domain\Dealer\Services\DealerService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Plan and billing for a dealer.
 *
 * Checkout only opens an order. The plan starts when `settle()` accepts a
 * verified callback, so an abandoned payment leaves the dealer on the plan
 * they already had.
 */
class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly DealerService $dealers,
    ) {}

    public function index(Request $request): View
    {
        $dealer = DashboardController::resolveDealer($request);

        if (! $dealer) {
            return view('dealer.no-profile');
        }

        return view('dealer.billing', [
            'dealer' => $dealer,
            'plans' => Plan::where('audience', 'dealer')->where('is_active', true)
                ->orderBy('sort_order')->get(),
            'subscription' => $dealer->activeSubscription()->with('plan')->first(),
            'usage' => $this->dealers->planUsage($dealer),
            'payments' => Payment::where('user_id', $request->user()->id)
                ->latest()->limit(10)->get(),
        ]);
    }

    public function checkout(Request $request, Plan $plan): JsonResponse
    {
        $dealer = DashboardController::resolveDealer($request);

        abort_unless($dealer, 403);

        if ($plan->audience !== 'dealer' || ! $plan->is_active) {
            return response()->json(['status' => 'error', 'message' => __('That plan is not available.')], 422);
        }

        $result = $this->billing->subscribe($dealer, $plan, $request->user());

        if (! $result['payment']) {
            return response()->json([
                'status' => 'ok',
                'message' => __('You are on the :plan plan.', ['plan' => $plan->name]),
                'data' => ['paid' => true],
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('Order created.'),
            'data' => [
                'paid' => false,
                'reference' => $result['payment']->reference_no,
                'confirm_url' => route('dealer.billing.confirm', $result['payment']),
                'checkout' => $result['checkout'],
                'key_id' => config('kj.payments.razorpay.key_id'),
            ],
        ]);
    }

    /** The gateway callback. Nothing is granted unless the signature checks out. */
    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $settled = $this->billing->settle($payment, $request->except('_token'));

        return response()->json([
            'status' => $settled ? 'ok' : 'error',
            'message' => $settled
                ? __('Payment received — your plan is active.')
                : __('We could not verify that payment. Nothing has been charged to your plan.'),
        ], $settled ? 200 : 422);
    }
}
