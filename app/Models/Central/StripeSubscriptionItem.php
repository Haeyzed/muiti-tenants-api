<?php

declare(strict_types=1);

namespace App\Models\Central;

use Laravel\Cashier\SubscriptionItem as CashierSubscriptionItem;

/**
 * Stripe subscription line item for a tenant subscription.
 */
class StripeSubscriptionItem extends CashierSubscriptionItem
{
    protected $table = 'stripe_subscription_items';
}
