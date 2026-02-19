<?php

namespace App\Services\Payment;

use App\Models\PaymentConfiguration;
use InvalidArgumentException;

class PaymentService
{
    /**
     * @param array $paymentGateway
     * @return object
     */
    public static function create(array $paymentGateway)
    {
        $paymentMethod = strtolower($paymentGateway['payment_method']);
        return match ($paymentMethod) {
            'paymob' => new PaymobPayment($paymentGateway),
            // 'phonepe' => new PhonePePayment($paymentGateway),
            // any other payment processor implementations
            default => throw new InvalidArgumentException('Invalid Payment Gateway.'),
        };
    }
}
