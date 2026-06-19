<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum WaitlistType: string
{
    case BackInStock = 'back_in_stock';
    case FlashSaleAlert = 'flash_sale_alert';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
