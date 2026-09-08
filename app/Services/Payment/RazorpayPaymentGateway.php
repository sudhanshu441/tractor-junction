<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay orders API. Amounts are sent in paise, and the callback signature is
 * verified with an HMAC over "order_id|payment_id" — never trust the client's
 * word that a payment succeeded.
 */
class RazorpayPaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $keyId,
        private readonly string $keySecret,
    ) {}

    public function createOrder(Payment $payment): array
    {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(15)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => (int) round((float) $payment->amount * 100),
                    'currency' => $payment->currency,
                    'receipt' => $payment->reference_no,
                ]);

            if ($response->failed()) {
                Log::error('Razorpay order failed', ['body' => $response->body()]);

                return ['order_id' => null, 'error' => __('Could not start the payment. Please try again.')];
            }

            return [
                'order_id' => $response->json('id'),
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'key_id' => $this->keyId,
                'test_mode' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('Razorpay exception: '.$e->getMessage());

            return ['order_id' => null, 'error' => __('Could not reach the payment provider.')];
        }
    }

    public function verify(Payment $payment, array $callback): bool
    {
        $orderId = $callback['razorpay_order_id'] ?? null;
        $paymentId = $callback['razorpay_payment_id'] ?? null;
        $signature = $callback['razorpay_signature'] ?? null;

        if (! $orderId || ! $paymentId || ! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret);

        return hash_equals($expected, $signature);
    }
}
