<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Product;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for product management.
 */
class ProductPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('products.view');
    }

    public function view(TenantUser $user, Product $product): bool
    {
        return $user->can('products.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('products.create');
    }

    public function update(TenantUser $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function delete(TenantUser $user, Product $product): bool
    {
        return $user->can('products.delete');
    }
}
