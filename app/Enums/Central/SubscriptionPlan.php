<?php

declare(strict_types=1);

namespace App\Enums\Central;

enum SubscriptionPlan: string
{
    case Starter = 'starter';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
