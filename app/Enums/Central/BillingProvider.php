<?php

declare(strict_types=1);

namespace App\Enums\Central;

enum BillingProvider: string
{
    case Stripe = 'stripe';
    case Paddle = 'paddle';
    case Paystack = 'paystack';
    case PayPal = 'paypal';
    case Flutterwave = 'flutterwave';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isGateway(): bool
    {
        return in_array($this, [self::Paystack, self::PayPal, self::Flutterwave], true);
    }
}
