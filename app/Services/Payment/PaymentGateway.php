<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentGateway
{
    /** Creates a gateway order and returns what the checkout needs. */
    public function createOrder(Payment $payment): array;

    /** Verifies a callback signature; false means do not mark anything paid. */
    public function verify(Payment $payment, array $callback): bool;
}
