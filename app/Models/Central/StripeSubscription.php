<?php

declare(strict_types=1);

namespace App\Models\Central;

use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * Stripe subscription record for a tenant.
 */
class StripeSubscription extends CashierSubscription
{
    protected $table = 'stripe_subscriptions';
}
