<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum CheckoutSessionStatus: string
{
    case Waiting = 'waiting';
    case Admitted = 'admitted';
    case Expired = 'expired';
    case Completed = 'completed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
