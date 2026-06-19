<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum TaxType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
