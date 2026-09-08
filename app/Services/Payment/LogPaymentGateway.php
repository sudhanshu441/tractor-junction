<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Local and staging driver. Records what a real gateway would have been asked
 * to do and treats every callback as valid, so plan upgrades and boosts can be
 * exercised end to end before live keys exist.
 */
class LogPaymentGateway implements PaymentGateway
{
    public function createOrder(Payment $payment): array
    {
        $orderId = 'order_test_'.Str::lower(Str::random(14));

        Log::info('[PAYMENT] order created', [
            'reference' => $payment->reference_no,
            'amount' => $payment->amount,
            'order_id' => $orderId,
        ]);

        return [
            'order_id' => $orderId,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'test_mode' => true,
        ];
    }

    public function verify(Payment $payment, array $callback): bool
    {
        Log::info('[PAYMENT] callback accepted in test mode', ['reference' => $payment->reference_no]);

        return true;
    }
}
