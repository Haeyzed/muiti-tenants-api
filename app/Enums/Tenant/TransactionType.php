<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum TransactionType: string
{
    case Charge = 'charge';
    case Refund = 'refund';
    case Authorization = 'authorization';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
