<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Order;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for order management.
 */
class OrderPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('orders.view') || $user->can('orders.create');
    }

    public function view(TenantUser $user, Order $order): bool
    {
        return $user->can('orders.view')
            || ($user->can('orders.create') && $order->customer_id === $user->customer?->id);
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('orders.create') && $user->customer !== null;
    }

    public function update(TenantUser $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }

    public function refund(TenantUser $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }
}
