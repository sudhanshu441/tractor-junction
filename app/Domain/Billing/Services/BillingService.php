<?php

namespace App\Domain\Billing\Services;

use App\Models\Dealer;
use App\Models\DealerSubscription;
use App\Models\ListingBoost;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\UsedListing;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Money in: dealer plan subscriptions and paid listing boosts.
 *
 * Nothing is granted at checkout — an order is only a promise. The plan or the
 * boost starts when a verified callback marks the payment paid, so a failed or
 * abandoned checkout can never leave a dealer holding a plan they did not buy.
 */
class BillingService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function nextReference(): string
    {
        $last = Payment::max('id') ?? 0;

        return 'KJ-P-'.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Opens a subscription in pending_payment and returns what the checkout
     * widget needs. A free plan skips the gateway entirely.
     *
     * @return array{payment: ?Payment, subscription: DealerSubscription, checkout: ?array}
     */
    public function subscribe(Dealer $dealer, Plan $plan, ?User $buyer = null): array
    {
        return DB::transaction(function () use ($dealer, $plan, $buyer) {
            $months = $this->cycleMonths($plan->billing_cycle);

            $subscription = $dealer->subscriptions()->create([
                'plan_id' => $plan->id,
                'starts_at' => today(),
                'ends_at' => today()->addMonths($months),
                'status' => (float) $plan->price > 0 ? 'pending_payment' : 'active',
            ]);

            if ((float) $plan->price <= 0) {
                $this->closePreviousSubscriptions($dealer, $subscription);

                return ['payment' => null, 'subscription' => $subscription, 'checkout' => null];
            }

            [$payment, $checkout] = $this->openPayment(
                $subscription,
                (float) $plan->price,
                $buyer ?? $dealer->owner,
            );

            return ['payment' => $payment, 'subscription' => $subscription, 'checkout' => $checkout];
        });
    }

    /**
     * A boost lifts one used listing in search results for a fixed window.
     *
     * @return array{payment: ?Payment, boost: ListingBoost, checkout: ?array}
     */
    public function boost(UsedListing $listing, Plan $plan, ?User $buyer = null): array
    {
        return DB::transaction(function () use ($listing, $plan, $buyer) {
            $days = $this->cycleMonths($plan->billing_cycle) * 30;

            $boost = ListingBoost::create([
                'used_listing_id' => $listing->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'starts_at' => now(),
                'ends_at' => now()->addDays($days),
                'status' => 'cancelled',   // becomes active only once paid
            ]);

            if ((float) $plan->price <= 0) {
                $this->activateBoost($boost);

                return ['payment' => null, 'boost' => $boost->refresh(), 'checkout' => null];
            }

            [$payment, $checkout] = $this->openPayment($boost, (float) $plan->price, $buyer ?? $listing->seller);

            return ['payment' => $payment, 'boost' => $boost, 'checkout' => $checkout];
        });
    }

    /**
     * Applies a gateway callback. The signature is checked before anything is
     * granted, and a payment already marked paid is never granted twice.
     */
    public function settle(Payment $payment, array $callback): bool
    {
        if ($payment->status === 'paid') {
            return true;
        }

        if (! $this->gateway->verify($payment, $callback)) {
            $payment->forceFill([
                'status' => 'failed',
                'gateway_response' => $callback,
            ])->save();

            return false;
        }

        return DB::transaction(function () use ($payment, $callback) {
            $payment->forceFill([
                'status' => 'paid',
                'gateway_payment_id' => $callback['razorpay_payment_id'] ?? $callback['payment_id'] ?? null,
                'gateway_response' => $callback,
            ])->save();

            $payable = $payment->payable;

            if ($payable instanceof DealerSubscription) {
                $payable->forceFill(['status' => 'active', 'payment_id' => $payment->id])->save();
                $this->closePreviousSubscriptions($payable->dealer, $payable);
            }

            if ($payable instanceof ListingBoost) {
                $payable->forceFill(['payment_id' => $payment->id])->save();
                $this->activateBoost($payable);
            }

            return true;
        });
    }

    /** @return array{0: Payment, 1: array} */
    private function openPayment(Model $payable, float $amount, ?User $buyer): array
    {
        $payment = Payment::create([
            'reference_no' => $this->nextReference(),
            'user_id' => $buyer?->id,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'amount' => $amount,
            'currency' => 'INR',
            'gateway' => config('kj.payments.driver') === 'razorpay' ? 'razorpay' : 'log',
            'status' => 'created',
        ]);

        $checkout = $this->gateway->createOrder($payment);

        $payment->forceFill(['gateway_order_id' => $checkout['order_id'] ?? null])->save();

        return [$payment->refresh(), $checkout];
    }

    /**
     * One live plan per dealer. The old subscription is expired rather than
     * deleted so the billing history stays readable.
     */
    private function closePreviousSubscriptions(Dealer $dealer, DealerSubscription $keep): void
    {
        $dealer->subscriptions()
            ->where('id', '!=', $keep->id)
            ->whereIn('status', ['active', 'pending_payment'])
            ->update(['status' => 'expired']);
    }

    private function activateBoost(ListingBoost $boost): void
    {
        $boost->forceFill([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(max(1, (int) round($boost->starts_at->diffInDays($boost->ends_at)))),
        ])->save();

        $boost->listing?->forceFill(['is_featured' => true])->save();
    }

    private function cycleMonths(?string $cycle): int
    {
        return match ($cycle) {
            'quarterly' => 3,
            'yearly' => 12,
            'one_time' => 1,
            default => 1,
        };
    }
}
