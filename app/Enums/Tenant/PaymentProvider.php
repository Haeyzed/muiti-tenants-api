<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum PaymentProvider: string
{
    case Stripe = 'stripe';
    case Paystack = 'paystack';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
