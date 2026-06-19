<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Cart;
use App\Models\Tenant\CartItem;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for shopping cart access.
 */
class CartPolicy
{
    public function view(TenantUser $user): bool
    {
        return $user->can('cart.manage') && $user->customer !== null;
    }

    public function manage(TenantUser $user): bool
    {
        return $user->can('cart.manage') && $user->customer !== null;
    }

    public function updateItem(TenantUser $user, CartItem $item): bool
    {
        return $user->can('cart.manage')
            && $item->cart->customer_id === $user->customer?->id;
    }

    public function deleteItem(TenantUser $user, CartItem $item): bool
    {
        return $this->updateItem($user, $item);
    }

    public function clear(TenantUser $user, Cart $cart): bool
    {
        return $user->can('cart.manage')
            && $cart->customer_id === $user->customer?->id;
    }
}
