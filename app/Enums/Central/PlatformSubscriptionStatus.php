<?php

declare(strict_types=1);

namespace App\Enums\Central;

enum PlatformSubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Trialing = 'trialing';
    case Cancelled = 'cancelled';
    case PastDue = 'past_due';
}
